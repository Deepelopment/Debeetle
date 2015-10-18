<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

use DirectoryIterator;
use InvalidArgumentException;
use Deepelopment\HTTP\Request;
use Deepelopment\Debeetle\Tree;
use Deepelopment\Debeetle\View\HTML;

/**
 * PHP Debug Tool main class.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @todo    Implement <dump labelTraceOffset="0" labelMaxCount="0" />
 * @todo    Implement Debeetle::checkpoint() ? plugin?
 */
class Tool implements ITool
{
    /**
     * Specifies to skip any actions if TRUE
     *
     * @var bool
     */
    protected $skip = TRUE;

    /**
     * Launch flag
     *
     * @var bool
     */
    protected $launch;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Tabs storage
     *
     * @var Tree
     */
    protected $tab;

    /**
     * View
     *
     * @var IView
     */
    protected $view;

    /**
     * Default options
     *
     * @var array
     */
    protected $options = array();

    /**
     * Array of printed labels to limit output by label
     *
     * @var array
     */
    protected $labels = array();

    /**
     * Plugins, array containing class names as keys and objects as values
     *
     * @var array
     */
    protected $plugins = array();

    /**
     * Virtual methods
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Trace info
     *
     * @var array|null
     */
    protected $trace;

    /**
     * Internal benches
     *
     * @var array
     */
    protected $bench;

    /**
     * @param array $settings  Array of settings
     * @see   Loader::startup()
     */
    public function __construct(array $settings)
    {
        $this->launch = !empty($settings['launch']);
        if ($this->launch) {
            $this->init($settings);
        }
    }

    /**
     * Magic caller.
     *
     * Calls methods registred using self::registerMethod().
     *
     * @param  string $method  Method name
     * @param  array  $args    Arguments
     * @return mixed
     * @see    Debeetle::registerMethod()
     * @see    Debeetle_TraceAndRun::init()
     */
    public function __call($method, array $args)
    {
        $this->startInternalBench();
        $result = null;
        if (isset($this->methods[$method])) {
            $this->setTrace(1);
            $result = call_user_func_array($this->methods[$method], $args);
            $this->resetTrace();
        }
        $this->finishInternalBench();
        return $result;
    }

    /**
     * Saves method caller.
     *
     * @param  int $offset  Offset in debug_backtrace() result
     * @return void
     */
    public function setTrace($offset)
    {
        if (!$this->trace) {
            $trace =
                version_compare(PHP_VERSION, '5.3.6', '>=')
                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                : debug_backtrace();
            $this->trace = array(
                'file' => $trace[$offset]['file'],
                'line' => $trace[$offset]['line']
            );
        }
    }

    /**
     * Returns method caller.
     *
     * @return array
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * Resets method caller.
     *
     * @return void
     */
    public function resetTrace()
    {
        $this->trace = null;
    }

    /**
     * Registers method.
     *
     * @param  string   $name      Method name
     * @param  callback $handler   Method handler
     * @param  bool     $override  Override existent handler
     * @return void
     * @throws InvalidArgumentException
     */
    public function registerMethod($name, array $handler, $override = FALSE)
    {
        // Check if handler is callable
        if (!is_callable($handler)) {
            if (self::ENV_PROD != self::$settings['env']) {
                throw new InvalidArgumentException("Invalid callback");
            } else {
                return;
            }
        }

        // Check if method is already registered
        if (!$override && isset($this->methods[$name])) {
            throw new InvalidArgumentException(
                sprintf(
                    "Method %s already registered",
                    $name
                )
            );
        }

        // Collect plugins objects
        if (
            is_array($handler) &&
            is_object($handler[0]) &&
            $handler[0] instanceof Debeetle_Plugin_Interface
        ) {
            $this->plugins[get_class($handler[0])] = $handler[0];
        }
        $this->methods[$name] = $handler;
    }

    /**
     * Calls passed method of each registered plugin.
     *
     * @param  string $method  Method
     * @param  array  $args
     * @return void
     */
    public function callPluginMethod($method, array $args = array())
    {
        foreach (array_keys($this->plugins) as $plugin) {
            call_user_func_array(
                array($this->plugins[$plugin], $method),
                $args
            );
        }
    }

    /**
     * Returns settings.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Sets self instance to the plugins.
     *
     * @return void
     */
    public function setInstance()
    {
        foreach (array_keys($this->plugins) as $plugin) {
            $this->plugins[$plugin]->setInstance($this);
        }
    }

    /**
     * Sets view instance.
     *
     * @param  IView $view  View object
     * @return void
     * @throws InvalidArgumentException
     */
    public function setView(IView $view)
    {
        if ($view instanceof IView) {
            $this->view = $view;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    "%s must implement IView interface",
                    get_class($view)
                )
            );
        }
    }

    /**
     * Returns view instance.
     *
     * @return IView
     * @todo   Think about registerView
     */
    public function getView()
    {
        if (!$this->view) {
            /*
            $data = new Deepelopment_EventData(
                array('class' => 'Debeetle_View_HTML')
            );
            if (!$this->skip) {
                $this->_evt->fire('bebeetle_on_get_view', $data);
            }
            $this->view = new $data->class($this->settings);
            */
            $this->view = new HTML($this->settings);
            /*
            if (!($this->view instanceof IView)){
                throw new Debeetle_Exception(
                    $data->class .
                        " must implement IView interface",
                    Debeetle_Exception::INVALID_CLASS_INTERFACE
                );
            }
            */
            $this->view->setTab($this->tab);
        }

        return $this->view;
    }

    /**
     * Sets default options for methods supporting options.
     *
     * @param  string $target   Target method name
     * @param  array  $options  Array of options
     * @return void
     */
    public function setDefaultOptions($target, array $options)
    {
        if ($this->skip) {
            return;
        }
        $this->settings['defaults']['options'][$target] = $options;
        $this->setInstance($this);
    }

    /**
     * Specifys target tab.
     *
     * Example:
     * <code>
     * // Will set active tab as 'Server', subtab as 'Log'
     * Debeetle:tab('Server|Log', 'Server|Checkpoints');
     * </code>
     *
     * @param  string $tab     Target tab
     * @param  string $before  Tab to specify tab order
     * @return void
     * @see    d::t()
     */
    public function tab($tab, $before = '')
    {
        if ($this->skip) {
            return;
        }
        $this->startInternalBench();
        /*
        if (!$this->settings['use_tabs'] || $tab === '') {
            return;
        }
        */
        $options =
            isset($this->options['write'])
            ? $this->options['write']
            : array();
        if(
            isset($options['encoding']) &&
            empty($options['skipEncoding']) &&
            $options['encoding'] !== 'UTF-8'
        ) {
            foreach (array('tab', 'before') as $var) {
                $$var =
                    mb_convert_encoding(
                        $$var,
                        'UTF-8',
                        $options['encoding']
                    );
            }
        }
        $this->tab->select($tab, $before, FALSE);
        $this->finishInternalBench();
    }

    /**
     * Writes string to debug output.
     *
     * Example:
     * <code>
     * Debeetle:write('Hi there!', 'Server|Log');
     * d::w('Hi there!', 'Server|Log');
     * </code>
     *
     * @param  string $string   String to write
     * @param  array  $options  Reserved array for functionality enhancement
     * @return void
     * @see    d::w()
     */
    public function write($string, array $options = array())
    {
        if ($this->skip) {
            return;
        }
        if (empty($options['skipInternalBench'])) {
            $this->startInternalBench();
        }
        if (isset($this->options['write'])) {
            $options += $this->options['write'];
        }
        if (isset($options['tab'])) {
            $tab = $this->tab->getLast();
            $caption = $options['tab'];
            if(
                isset($options['encoding']) &&
                empty($options['skipEncoding']) &&
                $options['encoding'] !== 'UTF-8'
            ) {
                $caption =
                    mb_convert_encoding(
                        $caption,
                        'UTF-8',
                        $options['encoding']
                    );
            }
            $this->tab->select($caption);
        }
        $string = $this->getView()->renderString($string, $options);
        $this->tab->send($string);
        if (isset($tab)) {
            $this->tab->select($tab);
        }
        if (empty($options['skipInternalBench'])) {
            $this->finishInternalBench();
        }
    }

    /**
     * Verifys printing data by label condition.
     *
     * @param  string $method   Debeetle method name
     * @param  string $label    Label
     * @param  array  $options  Options
     * @return bool
     */
    public function checkLabel($method, $label, array $options)
    {
        if (empty($this->labels[$method])) {
            $this->labels[$method] = array();
        }
        if (empty($this->labels[$method][$label])) {
            $this->labels[$method][$label] = 1;
        } else {
            $this->labels[$method][$label]++;
        }
        return
            empty($options['label_limit'])
            ? TRUE
            : $this->labels[$method][$label] <= $options['label_limit'];
    }

    /**
     * Returns internal benches.
     *
     * @return array
     */
    public function getInternalBenches()
    {
        return $this->bench;
    }

    /**
     * @param  array $settings  Array of settings
     * @return void
     */
    protected function init(array $settings)
    {
        $this->bench = array(
            'scriptStartupState' => $settings['scriptStartupState'],
            'startupState'       => $settings['startupState'],
            'skip'            => FALSE,
            'pmu'             => function_exists('memory_get_peak_usage')
        );
        unset($settings['scriptStartupState'], $settings['startupState']);
        $this->settings =
            $settings +
            array(
                'cookieName'   => 'debeetle_' . md5($_SERVER['HTTP_HOST']),
                'disabledTabs' => array(
                    'server' => array(),
                    'client' => array()
                )
            );

        $request = new Request;
        $cookie = $request->get($this->settings['cookieName'], null, INPUT_COOKIE);
        if ($cookie) {
            $cookie = json_decode($cookie, TRUE);
            if(!is_array($cookie)){
                $cookie = array();
            }
        } else {
            $cookie = array();
        }
        $this->settings['clientCookie'] = $cookie;
        $this->skip = empty($this->settings['clientCookie']['launch']);
        if (
            isset($cookie['disabledTabs']) &&
            is_array($cookie['disabledTabs'])
        ) {
            // var_dump($cookie['disabledTabs']);#die;###
            $this->settings['disabledTabs']['client'] =
                $cookie['disabledTabs'];
        }
        $this->tab = new Tree($this->settings);
        #var_dump($this->settings['disabledTabs']);die;###

        /*
        if (!$this->skip) {
            $this->_evt = new Deepelopment_Event;
        }
        */

        $scanForDefaults =
            empty($this->settings['defaults']['skin']) ||
            empty($this->settings['defaults']['theme']);
        if (
            $scanForDefaults ||
            (
                !in_array(
                    'Settings',
                    $this->settings['disabledTabs']['server']
                ) &&
                !in_array(
                    'Settings|Panel',
                    $this->settings['disabledTabs']['server']
                )
            )
        ) {
            $skins = array();
            $skinDir = new DirectoryIterator(
                $this->settings['path']['resources'] . '/skin'
            );
            foreach ($skinDir as $skin) {
                if ($skin->isDot() || !$skin->isDir()) {
                    continue;
                }
                $themePath =
                    $this->settings['path']['resources'] . '/skin/' .
                    $skin->getBasename() . '/theme';
                if (!is_dir($themePath)) {
                    continue;
                }
                $skinName = $skin->getBasename();
                $skins[$skinName] = array();
                $themeDir = new DirectoryIterator($themePath);
                foreach ($themeDir as $theme) {
                    if (
                        $theme->isDot() ||
                        !preg_match('/\.css$/', $theme->getBasename()))
                    {
                        continue;
                    }
                    $skins[$skinName][] = $theme->getBasename('.css');
                }
                if(empty($skins[$skinName])){
                    unset($skins[$skinName]);
                }
            }
            if (sizeof($skins)) {
                $this->settings['skins'] = $skins;
                if ($scanForDefaults) {
                    list($skin, $themes) = each($skins);
                    list(, $theme) = each($themes);
                    $this->settings['defaults'] += array(
                        'skin'  => $skin,
                        'theme' => $theme
                    );
                }
            }
        }
        $this->bench['onLoad'] = array(
            'includedFiles'   =>
                sizeof(get_included_files()) -
                $this->bench['startupState']['includedFiles'] + 1,
            'peakMemoryUsage' => 0,
            'memoryUsage'     =>
                memory_get_usage() - $this->bench['startupState']['memoryUsage']
        );
        if ($this->bench['pmu']) {
            $this->bench['onLoad']['peakMemoryUsage'] =
                memory_get_peak_usage() -
                $this->bench['startupState']['peakMemoryUsage'];
        }
        $this->bench['onLoad']['time'] =
            microtime(TRUE) -
            $this->bench['startupState']['time'];
        $this->bench['total'] = $this->bench['onLoad'] + array('qty' => 0);
    }

    /**
     * @return void
     * @todo Describe
     */
    protected function startInternalBench()
    {
        $this->bench['total']['qty']++;
        $this->bench['current'] = array(
            'includedFiles'   => sizeof(get_included_files()),
            'memoryUsage'     => memory_get_usage(),
            'peakMemoryUsage' =>
                $this->bench['pmu']
                    ? memory_get_peak_usage()
                    : 0,
            'time'            => microtime(TRUE)
        );
        // $e = new Exception;echo '<pre>'/*, var_export($this->bench['current'], TRUE)*/, $e->getTraceAsString(), '</pre>';###
    }

    /**
     * @return void
     * @todo Describe
     */
    protected function finishInternalBench()
    {
        // $e = new Exception;echo '<pre>'/*, var_export($this->bench['current'], TRUE)*/, $e->getTraceAsString(), '</pre>';###
        $this->bench['total']['includedFiles'] +=
            sizeof(get_included_files()) -
            $this->bench['current']['includedFiles'];
        $this->bench['total']['memoryUsage'] +=
            memory_get_usage() - $this->bench['current']['memoryUsage'];
        if ($this->bench['pmu']) {
            $this->bench['total']['peakMemoryUsage'] +=
                memory_get_peak_usage() -
                $this->bench['current']['peakMemoryUsage'];
        }
        $this->bench['total']['time'] +=
            microtime(TRUE) - $this->bench['current']['time'];
        unset($this->bench['current']);
    }
}

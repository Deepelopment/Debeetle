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
    protected $_skip = TRUE;

    /**
     * Launch flag
     *
     * @var bool
     */
    protected $_launch;

    /**
     * Settings
     *
     * @var array
     */
    protected $_settings;

    /**
     * Tabs storage
     *
     * @var Tree
     */
    protected $_tab;

    /**
     * View
     *
     * @var IView
     */
    protected $_view;

    /**
     * Default options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Array of printed labels to limit output by label
     *
     * @var array
     */
    protected $_labels = array();

    /**
     * Plugins, array containing class names as keys and objects as values
     *
     * @var array
     */
    protected $_plugins = array();

    /**
     * Virtual methods
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * Trace info
     *
     * @var array|null
     */
    protected $_trace;

    /**
     * Internal benches
     *
     * @var array
     */
    protected $_bench;

    /**
     * Constructor
     *
     * @param array $settings  Array of settings
     * @see   Loader::startup()
     */
    public function __construct(array $settings)
    {
        $this->_launch = !empty($settings['launch']);
        if ($this->_launch) {
            $this->init($settings);
        }
    }

    /**
     * Magic caller
     *
     * Calls methods registred using Debeetle::registerMethod().
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
        if (isset($this->_methods[$method])) {
            $this->setTrace(1);
            $result = call_user_func_array($this->_methods[$method], $args);
            $this->resetTrace();
        }
        $this->finishInternalBench();
        return $result;
    }

    /**
     * Save method caller
     *
     * @param  int $offset  Offset in debug_backtrace() result
     * @return void
     */
    public function setTrace($offset)
    {
        if (!$this->_trace) {
            $trace =
                version_compare(PHP_VERSION, '5.3.6', '>=')
                ? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                : debug_backtrace();
            $this->_trace = array(
                'file' => $trace[$offset]['file'],
                'line' => $trace[$offset]['line']
            );
        }
    }

    /**
     * Returns method caller
     *
     * @return array
     */
    public function getTrace()
    {
        return $this->_trace;
    }

    /**
     * Reset method caller
     *
     * @return void
     */
    public function resetTrace()
    {
        $this->_trace = null;
    }

    /**
     * Register method
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
        if (!$override && isset($this->_methods[$name])) {
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
            $this->_plugins[get_class($handler[0])] = $handler[0];
        }
        $this->_methods[$name] = $handler;
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
        foreach (array_keys($this->_plugins) as $plugin) {
            call_user_func_array(
                array($this->_plugins[$plugin], $method),
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
        return $this->_settings;
    }

    /**
     * Sets self instance to the plugins.
     *
     * @return void
     */
    public function setInstance()
    {
        foreach (array_keys($this->_plugins) as $plugin) {
            $this->_plugins[$plugin]->setInstance($this);
        }
    }

    /**
     * Set view instance
     *
     * @param  IView $view  View object
     * @return void
     * @throws InvalidArgumentException
     */
    public function setView(IView $view)
    {
        if ($view instanceof IView) {
            $this->_view = $view;
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
     * Returns view instance
     *
     * @return IView
     * @todo   Think about registerView
     */
    public function getView()
    {
        if (!$this->_view) {
            /*
            $data = new Deepelopment_EventData(
                array('class' => 'Debeetle_View_HTML')
            );
            if (!$this->_skip) {
                $this->_evt->fire('bebeetle_on_get_view', $data);
            }
            $this->_view = new $data->class($this->_settings);
            */
            $this->_view = new HTML($this->_settings);
            /*
            if (!($this->_view instanceof IView)){
                throw new Debeetle_Exception(
                    $data->class .
                        " must implement IView interface",
                    Debeetle_Exception::INVALID_CLASS_INTERFACE
                );
            }
            */
            $this->_view->setTab($this->_tab);
        }
        return $this->_view;
    }

    /**
     * Set default options for methods supporting options
     *
     * @param  string $target   Target method name
     * @param  array  $options  Array of options
     * @return void
     */
    public function setDefaultOptions($target, array $options)
    {
        if ($this->_skip) {
            return;
        }
        $this->_settings['defaults']['options'][$target] = $options;
        $this->setInstance($this);
    }

    /**
     * Specify target tab
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
        if ($this->_skip) {
            return;
        }
        $this->startInternalBench();
        /*
        if (!$this->_settings['use_tabs'] || $tab === '') {
            return;
        }
        */
        $options =
            isset($this->_options['write'])
            ? $this->_options['write']
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
        $this->_tab->select($tab, $before, FALSE);
        $this->finishInternalBench();
    }

    /**
     * Write string to debug output
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
        if ($this->_skip) {
            return;
        }
        if (empty($options['skipInternalBench'])) {
            $this->startInternalBench();
        }
        if (isset($this->_options['write'])) {
            $options += $this->_options['write'];
        }
        if (isset($options['tab'])) {
            $tab = $this->_tab->getLast();
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
            $this->_tab->select($caption);
        }
        $string = $this->getView()->renderString($string, $options);
        $this->_tab->send($string);
        if (isset($tab)) {
            $this->_tab->select($tab);
        }
        if (empty($options['skipInternalBench'])) {
            $this->finishInternalBench();
        }
    }

    /**
     * Verify printing data by label condition
     *
     * @param  string $method   Debeetle method name
     * @param  string $label    Label
     * @param  array  $options  Options
     * @return bool
     */
    public function checkLabel($method, $label, array $options)
    {
        if (empty($this->_labels[$method])) {
            $this->_labels[$method] = array();
        }
        if (empty($this->_labels[$method][$label])) {
            $this->_labels[$method][$label] = 1;
        } else {
            $this->_labels[$method][$label]++;
        }
        return
            empty($options['label_limit'])
            ? TRUE
            : $this->_labels[$method][$label] <= $options['label_limit'];
    }

    /**
     * Returns internal benches
     *
     * @return array
     */
    public function getInternalBenches()
    {
        return $this->_bench;
    }

    /**
     * Initialize Debeetle according to the settings
     *
     * @param  array $settings  Array of settings
     * @return void
     */
    protected function init(array $settings)
    {
        $this->_bench = array(
            'scriptStartupState' => $settings['scriptStartupState'],
            'startupState'       => $settings['startupState'],
            'skip'            => FALSE,
            'pmu'             => function_exists('memory_get_peak_usage')
        );
        unset($settings['scriptStartupState'], $settings['startupState']);
        $this->_settings =
            $settings +
            array(
                'cookieName'   => 'debeetle_' . md5($_SERVER['HTTP_HOST']),
                'disabledTabs' => array(
                    'server' => array(),
                    'client' => array()
                )
            );

        $request = new Request;
        $cookie = $request->get($this->_settings['cookieName'], null, INPUT_COOKIE);
        if ($cookie) {
            $cookie = json_decode($cookie, TRUE);
            if(!is_array($cookie)){
                $cookie = array();
            }
        } else {
            $cookie = array();
        }
        $this->_settings['clientCookie'] = $cookie;
        $this->_skip = empty($this->_settings['clientCookie']['launch']);
        if (
            isset($cookie['disabledTabs']) &&
            is_array($cookie['disabledTabs'])
        ) {
            // var_dump($cookie['disabledTabs']);#die;###
            $this->_settings['disabledTabs']['client'] =
                $cookie['disabledTabs'];
        }
        $this->_tab = new Tree($this->_settings);
        #var_dump($this->_settings['disabledTabs']);die;###

        /*
        if (!$this->_skip) {
            $this->_evt = new Deepelopment_Event;
        }
        */

        $scanForDefaults =
            empty($this->_settings['defaults']['skin']) ||
            empty($this->_settings['defaults']['theme']);
        if (
            $scanForDefaults ||
            (
                !in_array(
                    'Settings',
                    $this->_settings['disabledTabs']['server']
                ) &&
                !in_array(
                    'Settings|Panel',
                    $this->_settings['disabledTabs']['server']
                )
            )
        ) {
            $skins = array();
            $skinDir = new DirectoryIterator(
                $this->_settings['path']['resources'] . '/skin'
            );
            foreach ($skinDir as $skin) {
                if ($skin->isDot() || !$skin->isDir()) {
                    continue;
                }
                $themePath =
                    $this->_settings['path']['resources'] . '/skin/' .
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
                $this->_settings['skins'] = $skins;
                if ($scanForDefaults) {
                    list($skin, $themes) = each($skins);
                    list(, $theme) = each($themes);
                    $this->_settings['defaults'] += array(
                        'skin'  => $skin,
                        'theme' => $theme
                    );
                }
            }
        }
        $this->_bench['onLoad'] = array(
            'includedFiles'   =>
                sizeof(get_included_files()) -
                $this->_bench['startupState']['includedFiles'] + 1,
            'peakMemoryUsage' => 0,
            'memoryUsage'     =>
                memory_get_usage() - $this->_bench['startupState']['memoryUsage']
        );
        if ($this->_bench['pmu']) {
            $this->_bench['onLoad']['peakMemoryUsage'] =
                memory_get_peak_usage() -
                $this->_bench['startupState']['peakMemoryUsage'];
        }
        $this->_bench['onLoad']['time'] =
            microtime(TRUE) -
            $this->_bench['startupState']['time'];
        $this->_bench['total'] = $this->_bench['onLoad'] + array('qty' => 0);
    }

    protected function startInternalBench()
    {
        $this->_bench['total']['qty']++;
        $this->_bench['current'] = array(
            'includedFiles'   => sizeof(get_included_files()),
            'memoryUsage'     => memory_get_usage(),
            'peakMemoryUsage' =>
                $this->_bench['pmu']
                    ? memory_get_peak_usage()
                    : 0,
            'time'            => microtime(TRUE)
        );
        // $e = new Exception;echo '<pre>'/*, var_export($this->_bench['current'], TRUE)*/, $e->getTraceAsString(), '</pre>';###
    }

    protected function finishInternalBench()
    {
        // $e = new Exception;echo '<pre>'/*, var_export($this->_bench['current'], TRUE)*/, $e->getTraceAsString(), '</pre>';###
        $this->_bench['total']['includedFiles'] +=
            sizeof(get_included_files()) -
            $this->_bench['current']['includedFiles'];
        $this->_bench['total']['memoryUsage'] +=
            memory_get_usage() - $this->_bench['current']['memoryUsage'];
        if ($this->_bench['pmu']) {
            $this->_bench['total']['peakMemoryUsage'] +=
                memory_get_peak_usage() -
                $this->_bench['current']['peakMemoryUsage'];
        }
        $this->_bench['total']['time'] +=
            microtime(TRUE) - $this->_bench['current']['time'];
        unset($this->_bench['current']);
    }
}

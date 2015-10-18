<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\View;

use DirectoryIterator;
use Deepelopment\Debeetle\IView;
use Deepelopment\Debeetle\Tree;
use Deepelopment\Debeetle\Tree\Node;

/**
 * View.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class HTML implements IView
{
    const VERSION = '0.4.21a';

    /**
     * Settings
     *
     * @var array
     */
    protected $_settings;

    /**
     * @var Tree
     */
    protected $_tab;

    /**
     * View scope
     *
     * @var array
     */
    protected $_scope = array(
        'version' => self::VERSION
    );

    /**
     * Constructor
     *
     * @param array $settings  Array containing debug settings
     */
    public function __construct(array $settings = array())
    {
        $this->_scope =
                $settings['clientCookie'] +
                $settings['defaults'] +
                $this->_scope;
        $eol =
        $settings['eol'] =
            isset($settings['eol'])
                ? str_replace(array('\n', '\r'), array("\n", "\r"), $settings['eol'])
                : PHP_EOL;

        $this->_settings = $settings;
    }

    /**
     * Add scope
     *
     * @param  array $scope
     * @return self
     */
    public function addScope(array $scope)
    {
        $this->_scope = array_merge_recursive($this->_scope , $scope);

        return $this;
    }

    /**
     * Set tab object
     *
     * @param  Tree $tab
     * @return void
     */
    public function setTab(Tree $tab = NULL)
    {
        $this->_tab = $tab;
    }

    /**
     * Returns code initializing debugger.
     *
     * See {@link IView::get()} usage example.
     *
     * @return string  Appropriate HTML code
     */
    public function get()
    {
        if (is_null($this->_tab)) {
            // no output
            return '';
        }
        $shortAlias = $this->_settings['shortAlias'];
        extract($this->_scope);
        foreach (array('version', 'skin', 'theme') as $var) {
            $$var = rawurlencode($$var);
        }
        ob_start();
        $dir = new DirectoryIterator(
            $this->_settings['path']['resources'] . '/tabs'
        );
        $tabSettingsContent = '';
        foreach ($dir as $file) {
            $name = $file->getBasename('.phtml');
            if (
                $file->isDot() ||
                $file->isDir() ||
                !preg_match('/^\d+\./', $name))
            {
                continue;
            }
            $tab = preg_replace(
                array('/^\d+\./', '/_/'),
                array('', '|'),
                $name
            );
            /*
            $pos = mb_strpos($tab, '|', 0, 'ASCII');
            $place =
                $pos === FALSE
                    ? ''
                    :
                        'start:' .
                        mb_substr($tab, 0, $pos, 'ASCII') .
                        '##anywhere##';
            */
            if(
                in_array($tab, $this->_settings['disabledTabs']['server']) ||
                in_array($tab, $this->_settings['disabledTabs']['client'])
            ) {
                continue;
            }
            $shortAlias::t($tab);
            $content =
                file_get_contents(
                    $this->_settings['path']['resources'] . "/tabs/{$file}"
                );
            if ($content) {
                if ('Debeetle|Settings|Tabs' === $tab) {
                    $tabSettingsContent = $content;
                } else {
                    $shortAlias::w(
                        $content,
                        array('htmlEntities' => FALSE, 'nl2br' => FALSE)
                    );
                }
            }
            unset($content);
            if ($tab === 'Debeetle|Resource usage' && $this->_settings['developerMode']) {
                $shortAlias::getInstance()->callPluginMethod('displaySettings');
                $shortAlias::t($tab);
                $bench = $shortAlias::getInstance()->getInternalBenches();
                ob_start();
                require_once
                    $this->_settings['path']['resources'] .
                    '/tabs/Debeetle.resourceUsage.phtml';
                $shortAlias::w(
                    ob_get_clean(),
                    array('htmlEntities' => FALSE, 'nl2br' => FALSE)
                );
                unset($bench);
            }
        }

        if ($tabSettingsContent !== '') {
            // echo '<pre>';var_dump($this->_tab->getTree());die;###
            $html = $this->getTabSettings($this->_tab->getTree(), 0, '', FALSE);
            $content = str_replace('%%placeholder%%', $html, $tabSettingsContent);
            $shortAlias::t('Debeetle|Settings|Tabs');
            $shortAlias::w(
                $content,
                array('htmlEntities' => FALSE, 'nl2br' => FALSE)
            );
        }

        $tabs = $this->getTab($this->_tab->get());

        $data = array(
            'version'    => urldecode(self::VERSION),
            'cookieName' => $this->_settings['cookieName'],
            'path'       => $this->_settings['path'],
            'defaults'   => $this->_settings['defaults']
        );

        if(!empty($this->_settings['skins'])) {
            $data['skins'] = $this->_settings['skins'];
        }

        $bench = $shortAlias::getInstance()->getInternalBenches();
        $data['placeholder'] = 0;
        $hash = '';
        foreach ($this->_settings['plugins'] as $plugin) {
            $hash .= $plugin . '|' . $plugin::VERSION . '|';
        }
        $data['hash'] = md5($hash);
        $data['visibleVersion'] = self::VERSION;

        require_once $this->_settings['path']['resources'] . '/init.phtml';
        $content = ob_get_clean();
        unset($data, $tabs);

        $data = array();
        foreach (array(
            'serverTime',
            'pageTotalTime',
            'memoryUsage',
            'peakMemoryUsage',
            'includedFiles'
        ) as $key) {
            $data[$key] = $this->getDataByType($key, $bench);
        }
        $data = ',' .trim(json_encode($data), '{}');
        return str_replace(',"placeholder":0', $data, $content);
    }

    /**
     * Prepare string
     *
     * @param  string $string
     * @param  array  $options  Reserved array for functionality enhancement
     * @return string
     * @see    Debeetle::write()
     */
    public function renderString($string, array $options = array())
    {
        $options += array(
            'htmlEntities' => FALSE,
            'nl2br'        => TRUE
        );
        if(
            isset($options['encoding']) &&
            empty($options['skipEncoding']) &&
            $options['encoding'] !== 'UTF-8'
        ) {
            $string =
                mb_convert_encoding($string, 'UTF-8', $options['encoding']);
        }
        if ($options['htmlEntities']) {
            $string = htmlentities($string, ENT_COMPAT, 'UTF-8');
        }
        if ($options['nl2br']) {
            $string = nl2br($string);
        }
        return $string;
    }

    /**
     * Inserts into $content tabs HTML
     *
     * @return string
     */
    protected function getTabSettings(array $tabs, $offset, $parent, $disabled)
    {
        $html = '';
        // return '';###
        foreach ($tabs as $tab => $content) {
            $newParent = ($parent !== '' ? $parent . '|' : '') . $tab;
            $checked =
                $disabled ||
                in_array(
                    $newParent,
                    $this->_settings['disabledTabs']['client']
                );
            $html .=
                '<div style="margin-left: ' . ($offset * 10) .
                'px; margin-top: 3px;">' .
                    '<label style="cursor: pointer;">' .
                        '<input type="checkbox" value="' . $newParent . '"' .
                        (
                            $checked
                                ? ' checked="checked" source-checked="1"'
                                : ''
                        ) .
                        (
                            $disabled
                                ? ' disabled="disabled" source-disabled="1"'
                                : ''
                        ) .
                        ' onclick="return $d.Panel.onTabSettingsTabClick(this);" /> ' .
                $tab . '</label></div>';
            if (is_array($content)) {
                $html .=
                    $this->getTabSettings(
                        $content,
                        $offset + 1,
                        $newParent,
                        $checked
                    );
            }
        }
        return $html;
    }

    /**
     * Returns tabs HTML-code
     *
     * @param  array|Node $struct
     * @param  bool       $active
     * @return string
     */
    protected function getTab($struct, $active = FALSE)
    {
        if (is_array($struct)) {
            $tabs = array();
            foreach ($struct as $name => $nextStruct) {
                $tabs[] =
                    sprintf(
                        "%s: %s",
                        $this->prepareForJS($name),
                        $this->getTab(
                            $nextStruct,
                            is_object($nextStruct)
                                ? $nextStruct->isActive()
                                : $active
                        )
                    );
            }
            return
                sprintf(
                    "{%stabs: {%s}}",
                    $active ? 'active: true, ': '',
                    implode(',', $tabs)
                );
        } else if ($struct->isDisabled()) {
            return 'false';
        } else {
            return
                sprintf(
                    "{%scontent: %s}",
                    $active ? 'active: true, ': '',
                    $this->prepareForJS($struct->get(), $active)
                );
        }
    }

    /**
     * Returns debug bar data by type
     *
     * @param  string $type    See implementation
     * @param  array  $iBench  Internal Debeetle benches
     * @return array
     */
    protected function getDataByType($type, array $iBench)
    {
        /**
         * Bench settings
         */
        $bench = $this->_settings['bench'];
        $omit =
            isset($bench[$type]) &&
            isset($bench[$type]['omit'])
                ? explode(',', $bench[$type]['omit'])
                : array();
        // $secondValue = null;
        switch ($type) {
            case 'serverTime':
                $format =
                    isset($bench[$type]) &&
                    isset($bench[$type]['format'])
                        ? $bench[$type]['format']
                        : 'Y/m/d H:i:s O';
                return
                    array(
                        date($format, $iBench['scriptStartupState'][$type])
                    );
                break;
            case 'pageTotalTime':
                $toOmit = $iBench['scriptStartupState']['time'];
                if (in_array('debeetle', $omit)) {
                    $toOmit += $iBench['total']['time'];
                }
                $value = microtime(TRUE) - $toOmit;
                break;
            case 'memoryUsage':
                $toOmit = 0;
                if (in_array('scriptInit', $omit)) {
                    $toOmit += $iBench['scriptStartupState'][$type];
                }
                if (in_array('debeetle', $omit)) {
                    $toOmit += $iBench['total'][$type];
                }
                $value = memory_get_usage() - $toOmit;
                break;
            case 'peakMemoryUsage':
                $value = null;
                if (function_exists('memory_get_peak_usage')) {
                    $toOmit = 0;
                    if (in_array('scriptInit', $omit)) {
                        $toOmit += $iBench['scriptStartupState'][$type];
                    }
                    if (in_array('debeetle', $omit)) {
                        $toOmit += $iBench['total'][$type];
                    }
                    $value = memory_get_peak_usage() - $toOmit;
                }
                break;
            case 'includedFiles':
                $toOmit = 1;
                if (in_array('scriptInit', $omit)) {
                    $toOmit += $iBench['scriptStartupState'][$type];
                }
                if (in_array('debeetle', $omit)) {
                    $toOmit += $iBench['total'][$type];
                }
                $value =
                    sizeof(get_included_files()) - $toOmit;
                break;
        }
        $params =
            isset($bench[$type])
                ? $bench[$type]
                : array();
        if (isset($params['devider'])) {
            $value = $value / $params['devider'];
            /*
            if ($secondValue) {
                $secondValue = $secondValue / $params['devider'];
            }
            */
        }
        $warning = '';
        if (isset($params['critical']) && $value >= $params['critical']) {
            $warning = 'critical';
        } else if (isset($params['warning']) && $value >= $params['warning']) {
            $warning = 'warning';
        }
        if (isset($params['decimalDigits']) && !is_null($value)) {
            $value = number_format($value, $params['decimalDigits'], '.', '');
            /*
            if ($secondValue) {
                $secondValue =
                    number_format(
                        $secondValue,
                        $params['decimalDigits'],
                        '.',
                        ''
                    );
            }
            */
        }
        // $result = array($value . ($secondValue ? ':' . $secondValue : ''));
        $result = array($value);
        if ($warning || isset($params['unit'])) {
            $result[] = $warning;
            if (isset($params['unit'])) {
                $result[] = $params['unit'];
            }
        } else if (
            in_array($type, array('memoryUsage', 'peakMemoryUsage'))
        ) {
            $result[] = '';
            $result[] = 'bytes';
        }
        return $result;
    }

    /**
     * Prepares string for JS usage.
     *
     * @param  string $string  String to prepare
     * @return string
     * @todo   Try ro extract this method to rhe common library?
     */
    protected function prepareForJS($string)
    {
        return
            "'" .
            str_replace(
                array(
                   "'",
                   "\r\n",
                   "\n\r",
                   "\r",
                   "\n",
                   '\\\\',
                   '</',
                   '/>'
                ),
                array(
                   "\\'",
                   '\\n',
                   '\\n',
                   '\\n',
                   '\\n',
                   '\\\\\\\\',
                   "<' + '/",
                   "/' + '>"
                ),
                $string
            ) .
            "'";
    }
}

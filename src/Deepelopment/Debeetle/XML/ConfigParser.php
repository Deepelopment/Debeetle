<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\XML;

use RuntimeException;
use Deepelopment\XML\Parser;
use Deepelopment\HTTP\Request;

/**
 * XML configure parser.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @todo    Rename bound/request
 */
class ConfigParser extends Parser
{
    /**
     * Parses document and returns configuration.
     *
     * @return array
     * @throws RuntimeException
     */
    public function parse()
    {
        $config = array(
            'launch' => $this->element->getAttribute('launch', FALSE, 'bool')
        );
        if (!$config['launch']) {
            return $config;
        }

        foreach (
            array(
                'bench', 'configs', 'defaults', 'response', 'path', 'plugins',
                'logger'
            ) as $key
        ) {
            $config[$key] = array();
        }

        $request = new Request;

        // Parse config elements {

        foreach ($this->element->getByPath('configs') as $configElement) {
            if (!$configElement->getAttribute('launch', FALSE, 'bool')) {
                continue;
            }

            // Parse "shortAlias" tag {
            $shortAlias = $configElement->getByPath('shortAlias', FALSE, 'string');
            if ($shortAlias) {
                $config['shortAlias'] = (string)$shortAlias;
            }
            // } Parse "shortAlias" tag

            // Parse "bound" section {

            if ($configElement->getByPath('bound', FALSE)) {
                $bounds = array();

                foreach (
                    array(
                        'serverNames' => array('SERVER_NAME', 'name'),
                        'serverIPs'   => array('SERVER_ADDR', 'ip'),
                        'remoteIPs'   => array('REMOTE_ADDR', 'ip')
                    ) as $configKey => $keys
                ) {
                    list($key, $node) = $keys;
                    $value =
                        $configElement->getByPath(
                            "bound/{$configKey}",
                            FALSE, 'array'
                        );
                    if ($value && isset($value[$node])) {
                        $bounds[] =
                            is_array($value[$node])
                            ? in_array($_SERVER[$key], $value[$node])
                            : $_SERVER[$key] === $value[$node];
                    }
                }
                $value = $configElement->getByPath(
                    'bound/request',
                    FALSE
                );
                if ($value) {
                    $cookie = $request->get((string)$value, null, INPUT_COOKIE);
                    $bounds[] = !is_null($cookie);
                }

                $value =
                    (string)$configElement->getByPath('bound/hostName', '');
                if ($value !== '') {
                    $bounds[] = $_SERVER['HTTP_HOST'] === $value;
                }

                $conditionAND =
                    (string)$configElement->getByPath(
                        'bound/condition',
                        'AND'
                    ) === 'AND';
                $bound = $conditionAND ? (sizeof($bounds) > 0) : FALSE;
                foreach ($bounds as $value) {
                    if ($conditionAND) {
                        if (!$value) {
                            $bound = FALSE;
                            break;
                        }
                    } else {
                        $bound = $bound || $value;
                        if ($value) {
                            break;
                        }
                    }
                }
                if (!$bound) {
                    continue;
                }
            }

            // } Parse "bound" section

            // next line for debug purpose only
            $config['configs'][] = (string)$configElement->getAttribute('name');
            $element = $configElement->getByPath('path', FALSE);
            if ($element) {
                $config['path'] = (array)$element;
            }

            // Parse "config" tag attributes {

            foreach (array('developerMode') as $name) {
                $value = $configElement->getAttribute($name);
                if (!is_null($value)) {
                    $config[$name] = (bool)(string)$value;
                }
            }

            // } Parse "config" tag attributes
            // Parse "becnh" section {

            $benchElement = $configElement->getByPath('bench', FALSE);
            if ($benchElement) {
                $config += array('bench' => array());
                foreach (array(
                    'serverTime',
                    'pageTotalTime',
                    'memoryUsage',
                    'peakMemoryUsage',
                    'includedFiles'
                ) as $tag) {
                    $element = $benchElement->getByPath($tag, FALSE);
                    if ($element) {
                        foreach (array(
                            'devider',
                            'decimalDigits',
                            'unit',
                            'warning',
                            'critical',
                            'omit',
                            'format'
                        ) as $attribute) {
                            $value = (string)$element->getAttribute($attribute);
                            if ($value !== '') {
                                if (empty($config['bench'][$tag])) {
                                    $config['bench'][$tag] = array();
                                }
                                $config['bench'][$tag][$attribute] = $value;
                            }
                        }
                    }
                }
            }

            // } Parse "becnh" section
            // Parse "disabledTabs" tag {

            $value =
                $configElement->getByPath('disabledTabs', FALSE, 'array');
            if ($value && isset($value['tab'])) {
                $config['disabledTabs'] = array(
                    'server' =>
                        is_array($value['tab'])
                            ? $value['tab']
                            : array($value['tab']),
                    'client' => array()
                );
            }

            // } Parse "disabledTabs" tag
            // Parse "defaults" tag {

            $value =
                $configElement->getByPath('defaults', FALSE, 'array');
            if ($value && is_array($value)) {
                $configElement->toArray($value);
                $config['defaults'] =
                    array_merge_recursive($config['defaults'], $value);
            }

            // } Parse "defaults" tag
            // Parse "plugins" section {

            foreach ($configElement->getByPath('plugins', array()) as $pluginElement) {
                $pluginLaunch = $pluginElement->getAttribute('launch');
                if (!is_null($pluginLaunch) && !(string)$pluginLaunch) {
                    continue;
                }
                $plugin = $pluginElement->getAttribute('name', '', 'string');
                if ($plugin !== '') {
                    if (class_exists($plugin)) {
                        $config['plugins'][] = $plugin;
                        $methods = $pluginElement->children();
                        foreach ($methods as $method) {
                            if ($method->getName() === 'method') {
                                $methodName = (string)$method->getAttribute('name');
                                $options = $method->children();
                                foreach ($options as $option) {
                                    if (!isset($config['defaults']['options'][$methodName])) {
                                        $config['defaults']['options'][$methodName] = array();
                                    }
                                    $config['defaults']['options'][$methodName][$option->getName()] =
                                        (string)$option;
                                }
                            }
                        }
                    }
                }
            }

            // } Parse "plugins" section

            $responseElement = $configElement->getByPath('response', FALSE);
            if ($responseElement) {
                $config['response'] =
                    $responseElement->getAttributes() +
                    $config['response'];
            }

            $element = $configElement->getByPath('lessCSS', FALSE);
            if (
                $element &&
                $element->getAttribute('launch', FALSE, 'bool')
            ) {
                $config['lessCSS'] = TRUE;
            }

            $element = $configElement->getByPath('logger', FALSE);
            if (
                $element &&
                $element->getAttribute('launch', FALSE, 'bool')
            ) {
                $config['logger'] = $element->getAttributes() + $config['logger'];
            }
        }

        if ($config['launch']) {
            if (!isset($config['shortAlias'])) {
                throw new RuntimeException(
                    "Missing obligatory node 'configs/config/shortAlias'"
                );
            }
            // obligatory path keys
            foreach (array('resources', 'script', 'cookie') as $key) {
                if (!isset($config['path'][$key])) {
                    throw new RuntimeException(
                        sprintf(
                            "Missing obligatory node 'configs/config/path/%s'",
                            $key
                        )
                    );
                }
            }
        }

        // } Parse config elements

        return $config;
    }

    /**
     * Returns document root tag.
     *
     * @return string
     */
    protected function getDocumentRoot()
    {
        return 'debeetle';
    }

    /**
     * Returns element by path and type
     *
     * @param  string $path  XML path
     * @param  string $type  Element type
     * @return mixed
     */
    private function parseElement($path, $type)
    {
        $element = $this->element->getByPath($path, null);
        if (!is_null($element)) {
            $converter = $element->getAttribute('converter', '', 'string');
            if ($converter) {
                settype($element, 'string');
                $element = $converter($element);
            } else {
                settype($element, $type);
            }
        }
        return $element;
    }
}

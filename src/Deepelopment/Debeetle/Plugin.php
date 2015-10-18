<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

/**
 * Plugin abstract class.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class Plugin implements IPlugin
{
    /**
     * Debugger instance
     *
     * @var ITool
     */
    protected $tool;

    /**
     * Settings
     *
     * @var array
     */
    protected $settings;

    /**
     * Sets debeetle instance.
     *
     * @param  ITool $tool
     * @return void
     */
    public function setInstance(ITool $tool)
    {
        $this->tool = $tool;
        $this->settings = $this->tool->getSettings();
        $this->settings['eol'] =
            isset($this->settings['eol'])
                ? str_replace(
                    array('\n', '\r'),
                    array("\n", "\r"),
                    $this->settings['eol']
                )
                : PHP_EOL;
    }

    /**
     * Displays settings.
     *
     * @return void
     */
    public function displaySettings()
    {
        // No settings
    }

    /**
     * Processs separate request and return data.
     *
     * @retun mixed
     */
    public function processRequest()
    {
        // No request processing

        return NULL;
    }
}

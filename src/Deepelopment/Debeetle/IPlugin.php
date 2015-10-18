<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

/**
 * Plugin interface.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
interface IPlugin
{
    /**
     * Sets debeetle instance.
     *
     * @param  Debeetle_Interface $debeetle
     * @return void
     */
    public function setInstance(ITool $tool);

    /**
     * Initializes plugin.
     *
     * Plugin should register its methods using Debeetle::addMethod().
     *
     * @return void
     */
    public function init();

    /**
     * Displays settings if necessary.
     *
     * @return void
     */
    public function displaySettings();

    /**
     * Processes separate request and return data if necessary.
     *
     * @retun mixed
     */
    public function processRequest();
}

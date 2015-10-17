<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

/**
 * PHP Debug Tool main class interface.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
interface ITool
{
    /**
     * Magic caller.
     *
     * @param  string $method  Method name
     * @param  array  $args    Arguments
     * @return mixed
     */
    public function __call($method, array $args);

    /**
     * Saves method caller.
     *
     * @param  int $offset  Offset in debug_backtrace() result
     * @return void
     */
    public function setTrace($offset);

    /**
     * Returns method caller.
     *
     * @return array
     */
    public function getTrace();

    /**
     * Resets method caller.
     *
     * @return void
     */
    public function resetTrace();

    /**
     * Registers method.
     *
     * @param  string   $name      Method name
     * @param  callback $handler   Method handler
     * @param  bool     $override  Override existent handler
     * @return void
     */
    public function registerMethod($name, array $handler, $override = FALSE);

    /**
     * Calls passed method of each registered plugin.
     *
     * @param  string $method  Method
     * @param  array  $args    Arguments
     * @return void
     */
    public function callPluginMethod($method, array $args = array());

    /**
     * Returns settings.
     *
     * @return array
     */
    public function getSettings();

    /**
     * Sets self instance to the plugins.
     *
     * @return void
     */
    public function setInstance();

    /**
     * Sets view instance.
     *
     * @param  IView $view  View object
     * @return void
     */
    public function setView(IView $view);

    /**
     * Returns view instance.
     *
     * @return IView
     */
    public function getView();

    /**
     * Sets default options for methods supporting options.
     *
     * @param  string $target   Target method name
     * @param  array  $options  Array of options
     * @return void
     */
    public function setDefaultOptions($target, array $options);

    /**
     * Specifys target tab.
     *
     * @param  string $tab     Target tab
     * @param  string $before  Tab to specify tab order
     * @return void
     */
    public function tab($tab, $before = '');

    /**
     * Writes string to debug output.
     *
     * @param  string $string   String to write
     * @param  array  $options  Reserved array for functionality enhancement
     * @return void
     */
    public function write($string, array $options = array());

    /**
     * Verifys printing data by label condition.
     *
     * @param  string $method   Debeetle method name
     * @param  string $label    Label
     * @param  array  $options  Options
     * @return bool
     */
    public function checkLabel($method, $label, array $options);

    /**
     * Returns internal benches.
     *
     * @return array|NULL
     */
    public function getInternalBenches();
}

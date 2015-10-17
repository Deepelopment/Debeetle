<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

/**
 * View interface.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
interface IView
{
    /**
     * Returns code initializing debugger.
     *
     * Usage examples:
     * <code>
     * d::get()->getView()->get();
     * // will return JavaScript code initializing Debeetle
     * // <script type="text/javascript">
     * // <!--
     * // $d.startup(
     * // ...
     * // );
     * // -->
     * // </style>
     * </code>
     *
     * @return string  Appropriate HTML code
     */
    public function get();

    /**
     * Set tab object
     *
     * @param  Debeetle_Tree $tab
     * @return void
     */
    public function setTab(Tree $tab = NULL);

    /**
     * Render string
     *
     * @param  string $string   String
     * @param  array  $options  Reserved array for functionality enhancement
     * @return string
     * @see    Debeetle::write()
     */
    public function renderString($string, array $options = array());

}

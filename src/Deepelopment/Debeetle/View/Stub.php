<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\View;

use Deepelopment\Debeetle\IView;
use Deepelopment\Debeetle\Tree;

/**
 * Stub view.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class Stub implements IView
{
    /**
     * Returns code initializing debugger.
     * @return string  Appropriate HTML code
     */
    public function get()
    {
        return '';
    }

    /**
     * Set tab object
     *
     * @param  Debeetle_Tree $tab
     * @return void
     */
    public function setTab(Tree $tab = NULL)
    {
    }

    /**
     * Render string
     *
     * @param  string $string   String
     * @param  array  $options  Reserved array for functionality enhancement
     * @return string
     * @see    ITool::write()
     */
    public function renderString($string, array $options = array())
    {
        return '';
    }
}

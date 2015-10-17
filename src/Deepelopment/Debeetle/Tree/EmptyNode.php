<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\Tree;

/**
 * Tabs tree empty node.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class EmptyNode extends Node
{
    /**
     * Sends data to the tab.
     *
     * @param  string $data  Data to send
     * @return void
     */
    public function send($data)
    {
    }

    /**
     * Returns tab content.
     *
     * @return NULL
     */
    public function get()
    {
        return NULL;
    }
}

<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle\Tree;

/**
 * Tabs tree node.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class Node
{
    /**
     * Node name
     *
     * @var bool
     */
    protected $name;

    /**
     * Activity flag
     *
     * @var bool
     */
    protected $active;

    /**
     * Disabled flag
     *
     * @var bool
     */
    protected $disabled;

    /**
     * Tab content
     *
     * @var string
     */
    protected $content = '';

    /**
     * Constructor
     *
     * @param string $name
     * @param bool   $active
     */
    public function __construct($name, $active, $disabled = FALSE)
    {
        $this->name     = $name;
        $this->active   = $active;
        $this->disabled = $disabled;
    }

    /**
     * Sends data to the tab.
     *
     * @param  string $data  Data to send
     * @return void
     */
    public function send($data)
    {
        if (!$this->disabled) {
            $this->content .= (string)$data;
        }
    }

    /**
     * Returns node content.
     *
     * @return string
     */
    public function get()
    {
        return $this->content;
    }

    /**
     * Returns node name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns TRUE if node is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Returns TRUE if node is disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }
}

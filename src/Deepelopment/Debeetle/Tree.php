<?php
/**
 * PHP Deepelopment Framework.
 *
 * @package Deepelopment/Debeetle
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Debeetle;

use Deepelopment\Debeetle\Tree\Node;
use Deepelopment\Debeetle\Tree\EmptyNode;

/**
 * Tabs tree.
 *
 * @package Deepelopment/Debeetle
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class Tree
{
    /**
     * Developer mode flag
     *
     * @var bool
     */
    protected $_developerMode;

    /**
     * Array of tabs content
     *
     * @var array
     */
    protected $_content = array();

    /**
     * Array of hidden tab names
     *
     * @var array
     */
    protected $_hidden = array();

    /**
     * The pointer to the current tab
     *
     * @var Node
     */
    protected $_current;

    /**
     * Last tab name
     *
     * @var string
     */
    protected $_last = '';

    /**
     * Last pointer
     *
     * @var mixed
     * @see Manager::setPointer()
     */
    protected $_pointer;

    /**
     * Array of disabled tabs
     *
     * @var array
     */
    protected $_disabledTabs;

    /**
     * Flag specifying to check for disabled tabs
     *
     * @var bool
     */
    protected $_checkDisabled = FALSE;

    /**
     * Empty tree node
     *
     * @var type EmptyNode
     */
    protected $_emptyElement;

    /**
     * Constructor
     *
     * @param array $settings  Array containing debug settings
     */
    public function __construct(array $settings = array())
    {
        $this->_developerMode = !empty($settings['developerMode']);
        if (isset($settings['disabledTabs'])) {
            $this->_disabledTabs = $settings['disabledTabs'];
            $this->_checkDisabled =
                !empty($this->_disabledTabs['server']) ||
                !empty($this->_disabledTabs['client']);
            if ($this->_checkDisabled) {
                $this->_emptyElement = new EmptyNode('', FALSE);
            }
        }
        $this->_current = $this->_emptyElement;///$this->getElement('');
    }

    /**
     * Select tab
     *
     * <code>
     * d::t('PHP|Log');
     * d::t('PHP|Console', 'before:PHP|Log');
     * d::t('Tab', 'PHP|xxx##anywhere');
     * </code>
     *
     * @param  string $name    Tab name
     * @param  string $places  Target places i.e.
     * '[after:]PHP|Console##before:PHP|Log##start:Tab##end:Tab##anywhere'
     * @param  bool   $active  Specifies tab activity
     * @return void
     * @throws Exception  if cannot add tab at specified places,
     *                                 developer mode only
     * @see
     */
    public function select($name, $places = '', $active = FALSE)
    {
        if (in_array($name, $this->_hidden)) {
            return;
        }
        if ($this->setPointer($name, $places === '', $active)) {
            return $this->releasePointer(true);
        }
        $explodedPlaces = explode('##', $places);
        foreach ($explodedPlaces as $place) {
            if ($place == 'anywhere') {
                $this->setPointer($name, TRUE, $active);
                $this->releasePointer(TRUE);
                return;
            }
            if (!preg_match('/^(before|after|start|end):/', $place)){
                $place = 'after:' . $place;
            }
            list ($place, $targetName) = explode(':', $place, 2);
            $explodedName = explode('|', $name);
            $explodedTargetName = explode('|', $targetName);
            $lastNamePart = array_pop($explodedName);
            $atBorder = $place != 'start' && $place != 'end';
            $lastTargetPart =
                $atBorder
                ? array_pop($explodedTargetName)
                : $explodedTargetName[sizeof($explodedTargetName) - 1];
            if ($explodedName == $explodedTargetName) {
                // $name can be placed near specified $targetName
                if (!empty($explodedTargetName)) {
                    $this->setPointer(
                        implode('|', $explodedTargetName),
                        !$atBorder,
                        $active
                    );
                }
                if (!is_array($this->_pointer)) {
                    $this->_pointer = array();
                }
                $keys = array_keys($this->_pointer);
                $index = array_search($lastTargetPart, $keys);
                $element = array(
                    $lastNamePart => $this->getElement($lastTargetPart)
                );
                if ($index === FALSE && $atBorder) {
                    continue;
                }
                switch ($place) {
                    case 'after':
                        $this->_pointer =
                            $index === (sizeof($this->_pointer) - 1)
                            ? $this->_pointer + $element
                            : (
                                array_slice(
                                    $this->_pointer, 0, $index + 1, TRUE
                                ) + $element +
                                array_slice(
                                    $this->_pointer,
                                    $index + 1,
                                    sizeof($this->_pointer) - $index - 1,
                                    TRUE
                                )
                            );
                        break;
                    case 'start':
                        $this->_pointer = $element + $this->_pointer;
                        $this->setPointer($name, FALSE, $active);
                        break;
                    case 'end':
                        $this->_pointer += $element;
                        $this->setPointer($name, FALSE, $active);
                        break;
                    case 'before':
                        $this->_pointer =
                            $index === 0
                            ? $element + $this->_pointer
                            : (
                                array_slice($this->_pointer, 0, $index, TRUE) +
                                $element +
                                array_slice(
                                    $this->_pointer,
                                    $index,
                                    sizeof($this->_pointer) - $index,
                                    TRUE
                                )
                            );
                        break;
                }
                $this->releasePointer(TRUE);
                return;
            }
        }
        if ($this->_developerMode) {
            $this->releasePointer(FALSE);
            throw new Exception(
                "Cannot add tab '{$name}' to '{$places}'",
                Exception::CANNOT_ADD
            );
        } else {
            $this->setPointer($name, TRUE, $active);
            $this->releasePointer(TRUE);
        }
    }

    /**
     * Send data to the tab
     *
     * @param  mixed $data  Data to send
     * @return void
     */
    public function send($data)
    {
        $this->_current->send($data);
    }

    /**
     * Returns last tab name
     *
     * @return string
     */
    public function getLast()
    {
        return $this->_last;
    }

    /**
     * Returns tabs
     *
     * @return array
     */
    public function get()
    {
        return $this->_content;
    }

    /**
     * Returns tabs as tree
     *
     * @return array
     */
    public function getTree()
    {
        return $this->getTreePart($this->_content);
    }

    /**
     * Returns tabs as tree from passed node
     *
     * @param  array $content
     * @return array
     */
    protected function getTreePart(array $content){
        $result = array();
        foreach ($content as $tab => $child) {
            $result[$tab] = null;
            if (is_array($child)) {
                $result[$tab] = $this->getTreePart($child);
            }
            /*
            else {
                echo '<pre>';
                var_dump($tab);var_dump($child);echo '</pre>';die;###
            }
            */
        }
        return $result;
    }

    /**
     * Returns new tab element
     *
     * @param  string $name
     * @param  bool   $active
     * @param  bool   $disabled
     * @return Element_Interface
     */
    protected function getElement($name, $active = FALSE, $disabled = FALSE)
    {
        return new Node($name, $active, $disabled);
    }

    /**
     * Returns pointer or checks name duplication
     *
     * @param  string $name    Name
     * @param  bool   $create  TRUE if need to create
     * @param  bool   $active  Activity flag
     * @return bool            TRUE if created or found
     */
    protected function setPointer($name, $create = FALSE, $active = FALSE)
    {
        $names = explode('|', $name);
        $this->_pointer = &$this->_content;
        $lastIndex = sizeof($names) - 1;
        $tab = array();
        foreach ($names as $index => $name) {
            if ($this->_checkDisabled) {
                $tab[] = $name;
                $tabPart = implode('|', $tab);
                if (in_array($tabPart, $this->_disabledTabs['server'])) {
                    // Set pointer to the empty tab element
                    $this->_pointer = &$this->_emptyElement;
                    return TRUE;
                }
            }
            if (is_object($this->_pointer)) {
                // If element content isn't empty, return element
                $this->_pointer =
                    $this->_pointer->get() !== ''
                    ? array($this->_pointer->getName() => $this->_pointer)
                    : array();
            }
            if (isset($this->_pointer[$name])) {
                $this->_pointer = &$this->_pointer[$name];
            } else {
                if ($create) {
                    $this->_pointer[$name] =
                        $index < $lastIndex
                            ? array()
                            : $this->getElement(
                                $name,
                                $active,
                                $this->_checkDisabled &&
                                in_array(
                                    $tabPart,
                                    $this->_disabledTabs['client']
                                )
                            );
                    $this->_pointer = &$this->_pointer[$name];
                } else {
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Cleanup pointer reference
     *
     * @param  bool  $setCurrent  Set current tab pointer on success
     * @return void
     */
    protected function releasePointer($setCurrent)
    {
        if ($setCurrent) {
            $this->_current = $this->_pointer;
        }
        // Cleanup reference
        unset($this->_pointer);
    }
}

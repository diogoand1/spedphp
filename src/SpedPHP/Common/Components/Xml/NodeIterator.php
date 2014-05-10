<?php

namespace SpedPHP\Common\Components\Xml;

/**
 * @category   SpedPHP
 * @package    SpedPHP
 * @copyright  Copyright (c) 2012
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @author     Antonio Spinelli <tonicospinelli85@gmail.com>
 */
class NodeIterator implements RecursiveIterator
{

    /**
     * Current Position in DOMNodeList
     * @var Integer
     */
    protected $position;

    /**
     * The DOMNodeList with all children to iterate over
     * @var \DOMNodeList
     */
    protected $nodeList;

    /**
     * @param \DOMNode $domNode
     * @return void
     */
    public function __construct(\DOMNode $domNode)
    {
        $this->position = 0;
        $this->nodeList = $domNode->childNodes;
    }

    /**
     * Returns the current DOMNode
     * @return \DOMNode
     */
    public function current()
    {
        return $this->nodeList->item($this->position);
    }

    /**
     * Returns an iterator for the current iterator entry
     * @return \RecursiveDOMIterator
     */
    public function getChildren()
    {
        return new self($this->current());
    }

    /**
     * Returns if an iterator can be created for the current entry.
     * @return Boolean
     */
    public function hasChildren()
    {
        return $this->current()->hasChildNodes();
    }

    /**
     * Returns the current position
     * @return Integer
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Moves the current position to the next element.
     * @return void
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * Rewind the Iterator to the first element
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Checks if current position is valid
     * @return Boolean
     */
    public function valid()
    {
        return $this->position < $this->nodeList->length;
    }
}

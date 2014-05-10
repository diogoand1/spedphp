<?php

namespace SpedPHP\Common\Components\Xml;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Components\Xml
 * @copyright  Copyright (c) 2012
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @author     Antonio Spinelli <tonicospinelli85@gmail.com>
 */
class Element extends \DOMElement
{

    /**
     *
     * @return \SpedPHP\Common\Components\Xml\NodeIterator
     */
    public function getIterator()
    {
        return new NodeIterator($this);
    }

    /**
     * Adds new child at the end of the children.
     *
     * @param \DOMNode $newNode The appended child.
     * @param boolean  $unique  [optional]>
     *                          If sets TRUE, search if exists the same node.
     * 
     *
     * @return DOMNode The node added or if is unique, returns the node found.
     */
    public function appendChild(\DOMNode $newNodeIn, $unique = false)
    {
        $node = null;
        if ($unique) {
            $node = parent::getElementsByTagName($newNodeIn->localName)->item(0);
        }
        if ($node !== null) {
            $newNode = parent::replaceChild($newNodeIn, $node);
        } else {
            $newNode = parent::appendChild($newNodeIn);
        }
        return $newNode;
    }

    /**
     * Adds a new child before a reference node
     *
     * @link http://php.net/manual/en/domnode.insertbefore.php
     *
     * @param DOMNode $newnode The new node.
     * @param DOMNode $refnode [optional] 
     * The reference node. If not supplied, newnode is appended to the children.
     * @return DOMNode The inserted node.
     */
    public function insertBefore(\DOMNode $newnode, \DOMNode $refnode = null)
    {
        $newNode = parent::insertBefore($newnode, $refnode);

        return $newNode;
    }

    /**
     * (PHP 5)<br/>
     * Replaces a child
     *
     * @link http://php.net/manual/en/domnode.replacechild.php
     *
     * @param DOMNode $newnode 
     * The new node. It must be a member of the target document, i.e.
     * created by one of the DOMDocument->createXXX() methods or imported in
     * the document by .
     * 
     * @param DOMNode $oldnode The old node.
     * @return DOMNode The old node or false if an error occur.
     */
    public function replaceChild(\DOMNode $newnode, \DOMNode $oldnode)
    {
        $newNode = parent::replaceChild($newnode, $oldnode);
        return $newNode;
    }

    /**
     * Removes children by tag name from list of children
     *
     * @link http://php.net/manual/en/domnode.removechild.php
     * @param string $tagName The tagName to remove children.
     * @return boolean If the child could be removed the function returns true.
     */
    public function removeElementsByTagName($name)
    {
        $nodes = $this->getElementsByTagName($name);
        foreach ($nodes as $node) {
            $this->removeChild($node);
        }
        return true;
    }

    /**
     * Get an XPath for a node
     *
     * @link http://php.net/manual/en/domnode.getnodepath.php
     * @return string a string containing the XPath, or NULL in case of an error.
     */
    public function getNodeXPath()
    {
        $result = '';
        $node = $this;
        while ($parentNode = $node->parentNode) {
            $nodeIndex = -1;
            $nodeTagIndex = 0;
            $hasSimilarNodes = false;
            do {
                $nodeIndex++;
                $testNode = $parentNode->childNodes->item($nodeIndex);

                if ($testNode->nodeName == $node->nodeName
                    and $testNode->parentNode->isSameNode($node->parentNode)
                    and $testNode->childNodes->length > 0
                ) {
                    $nodeTagIndex++;
                }
            } while (!$node->isSameNode($testNode));

            if ($hasSimilarNodes) {
                $result = "/{$node->nodeName}[{$nodeTagIndex}]" . $result;
            } else {
                $result = "/{$node->nodeName}" . $result;
            }
            $node = $parentNode;
        };

        return $result;
    }
}

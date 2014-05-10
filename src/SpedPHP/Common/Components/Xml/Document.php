<?php

namespace SpedPHP\Common\Components\Xml;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Components\Xml
 * @copyright  Copyright (c) 2012
 * @license    http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @author     Antonio Spinelli <tonicospinelli85@gmail.com>
 */
class Document extends \DOMDocument
{

    const ATTRIBUTES = '__ATTRIBUTES__';
    const CONTENT = '__CONTENT__';

    /**
     * Namespace's list.
     *
     * @var array
     */
    protected $namespaces;
    /**
     * __construct
     * @param string $version
     * @param string $charset
     */
    public function __construct($version = '1.0', $charset = 'UTF-8')
    {
        parent::__construct($version, $charset);
        parent::registerNodeClass('\DOMElement', '\SpedPHP\Common\Components\Xml\Element');
    }

    /**
     * Adds new child at the end of the children.
     *
     * @param \DOMNode $newNode The appended child.
     * @param boolean  $unique  If sets TRUE, search if exists the same node.
     * @return \DOMNode The node added or if is unique, returns the node found.
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
     * Removes children by tag name from list of children
     *
     * @link http://php.net/manual/en/domnode.removechild.php
     * @param string $name The tagName to remove children.
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
     * Return array of namespaces of the document
     * @param boolean $short
     * @return array
     */
    public function getNamespaces($short = false)
    {
        $xpath = new \DOMXPath($this);
        $query = "namespace::*";
        $this->namespaces = array();
        $namespaces = $xpath->query($query);
        foreach ($namespaces as $node) {
            if (preg_match("/:/", $node->nodeName)) {
                if ($short) {
                    list ($pref, $key) = explode(":", $node->nodeName);
                } else {
                    $key = $node->nodeName;
                }
                $this->namespaces[$key] = $node->nodeValue;
            }
        }
        return $this->namespaces;
    }

    /**
     * Return the target namespace this document
     *
     * @return string
     */
    public function getTargetNamespace()
    {
        $xpath = new \DOMXPath($this);
        $query = "/*/@targetNamespace";
        $this->namespaces = array();
        $targetNS = $xpath->query($query);
        foreach ($targetNS as $node) {
            return $node->nodeValue;
        }
        return null;
    }

    /**
     * QName string (ex, ns1:Name)
     * 
     * @param string  $qname
     * @param boolean $resolveNamespace [optional] Resolve namespace code to full form
     *
     * @throws \RuntimeException
     * @return array Returns array with namespace and nodeName.
     */
    public function parseQName($qname, $resolveNamespace = false)
    {
        if (!$this->isQName($qname)) {
            throw new \RuntimeException("O argumento fornecido não é tipo QName : " . $qname);
        }
        list ($ns, $name) = explode(":", $qname);
        if ($resolveNamespace === true) {
            $ns = $this->resolveNamespace($qname);
        }
        return array($ns, $name);
    }
    
    /**
     * isQName
     * 
     * @param string $name
     * @return boolean
     */
    public function isQName($name)
    {
        return (bool) preg_match('/:/', $name);
    }

    /**
     * Resolve short namespace to long, or return the same code if not found
     *
     * @param string $shortNamespace
     * @param bool   $fromShort
     *
     * @return string Long namespace
     */
    public function resolveNamespace($shortNamespace, $fromShort = false)
    {
        $namespaces = $this->getNamespaces($fromShort);
        if (array_key_exists($shortNamespace, $namespaces)) {
            return $this->namespaces[$shortNamespace];
        } else {
            return $shortNamespace;
        }
    }

    /**
     * @param string $longNamespace
     * @param bool   $short
     *
     * @return bool|string
     */
    public function getNamespaceCode($longNamespace, $short = false)
    {
        $namespaces = array_flip($this->getNamespaces());
        if (array_key_exists($longNamespace, $namespaces)) {
            $code = $namespaces[$longNamespace];
            if ($short === true) {
                $codeQName = $this->parseQName($namespaces[$longNamespace]);
                $code = $codeQName[1];
            }
            return $code;
        }
        return false;
    }

    /**
     * Create xml document from array
     *
     * @param array  $source
     * This source array:
     * <code>
     * Array(
     *  [book] => Array(
     *    [0] => Array(
     *      [author] => Author0
     *      [title] => Title0
     *      [publisher] => Publisher0
     *      [__ATTRIBUTES__] => Array(
     *        [isbn] => 978-3-16-148410-0
     *      )
     *    )
     *    [1] => Array(
     *      [author] => Array(
     *        [0] => Author1
     *        [1] => Author2
     *      )
     *      [title] => Title1
     *      [publisher] => Publisher1
     *    )
     *    [2] => Array(
     *      [__attributes__] => Array(
     *        [isbn] => 978-3-16-148410-0
     *      )
     *      [__content__] => Title2
     *    )
     *  )
     * )
     * </code>
     *
     * will produce this XML:
     *
     * <code>
     * <root>
     *   <book isbn="978-3-16-148410-0">
     *     <author>Author0</author>
     *     <title>Title0</title>
     *     <publisher>Publisher0</publisher>
     *   </book>
     *   <book>
     *     <author>Author1</author>
     *     <author>Author2</author>
     *     <title>Title1</title>
     *     <publisher>Publisher1</publisher>
     *   </book>
     *   <book isbn="978-3-16-148410-0">Title2</book>
     * </root>
     * </code>
     * @param string $rootTagName
     *
     * @return Document
     */
    public static function arrayToXml(array $source, $rootTagName = null)
    {
        $document = new Document();
        $document->appendChild(self::createDOMElement($source, $document, $rootTagName));
        return $document;
    }

    /**
     * @param \DOMDocument $document
     *
     * @return array
     */
    public static function xmlDocumentToArray(\DOMDocument $document)
    {
        return self::createArray($document->documentElement);
    }

    /**
     * createDOMElement 
     * 
     * @param mixed        $source
     * @param \DOMDocument $document
     * @param string       $tagName
     *
     * @return \DOMNode
     */
    private static function createDOMElement($source, \DOMDocument $document, $tagName = null)
    {
        if (!is_array($source)) {
            $element = $document->createElement($tagName);
            $element->appendChild($document->createCDATASection($source));
            return $element;
        }
        $element = is_null($tagName) ? $document->documentElement : $document->createElement($tagName);
        foreach ($source as $key => $value) {
            if (is_string($key)) {
                if ($key == self::ATTRIBUTES) {
                    foreach ($value as $attributeName => $attributeValue) {
                        $element->setAttribute($attributeName, $attributeValue);
                    }
                } elseif ($key == self::CONTENT) {
                    $element->appendChild($document->createCDATASection($value));
                } else {
                    foreach ((is_array($value) ? $value : array_values(array($value))) as $elementValue) {
                        $element->appendChild(self::createDOMElement($elementValue, $document, $key));
                    }
                }
            } else {
                $element->appendChild(self::createDOMElement($value, $document, $tagName));
            }
        }
        return $element;
    }

    /**
     * @param \DOMNode $domNode
     *
     * @return array
     */
    private static function createArray(\DOMNode $domNode)
    {
        $array = array();
        for ($i = 0; $i < $domNode->childNodes->length; $i++) {
            $item = $domNode->childNodes->item($i);
            if ($item->nodeType == XML_ELEMENT_NODE) {
                $arrayElement = array();
                for ($attributeIndex = 0;
                    !is_null($attribute = $item->attributes->item($attributeIndex));
                    $attributeIndex++) {
                    if ($attribute->nodeType == XML_ATTRIBUTE_NODE) {
                        $arrayElement[self::ATTRIBUTES][$attribute->nodeName] = $attribute->nodeValue;
                    }
                }
                $children = self::createArray($item);
                if (is_array($children)) {
                    $arrayElement = array_merge($arrayElement, $children);
                } else {
                    $arrayElement[self::CONTENT] = $children;
                }
                $array[$item->nodeName][] = $arrayElement;
            } elseif ($item->nodeType == XML_CDATA_SECTION_NODE ||
                ($item->nodeType == XML_TEXT_NODE && trim($item->nodeValue) != '')) {
                return $item->nodeValue;
            }
        }
        return $array;
    }
}

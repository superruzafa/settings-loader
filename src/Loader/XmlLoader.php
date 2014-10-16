<?php

namespace Superruzafa\Settings\Loader;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Superruzafa\Settings\Loader;

class XmlLoader implements Loader
{
    /** @var string */
    const SETTINGS_LOADER_XMLNS = 'http://github.com/superruzafa/settings-loader';

    /** @var array */
    private $settings = array();

    /**
     * Creates a new XmlLoader
     *
     * @param   DOMDocument $xml
     */
    public function __construct(DOMDocument $xml)
    {
        $this->xpath = new DOMXPath($xml);
        $this->xpath->registerNamespace('s', self::SETTINGS_LOADER_XMLNS);
    }

    /** @inheritdoc */
    public function getSettings()
    {
        return $this->settings;
    }

    /** @inheritdoc */
    public function load()
    {
        $this->settings = array();
        $this->walkSubNodes($this->xpath->document, array());
        return true;
    }

    /**
     * Recursively walks down all settings nodes contained in the current node
     *
     * @param   DOMNode $node
     * @param   array   $context
     */
    private function walkSubNodes(DOMNode $node, array $context)
    {
        $nodes = $this->query('s:abstract | s:settings', $node);
        foreach ($nodes as $node) {
            $this->parseSettings($node, $context);
        }
    }

    /**
     * Updates the context specified in a settings node.
     *
     * If it's a concrete settings the context will be added to the whole settings.
     * If it's an abstract node the context will be changed but not added.
     *
     * @param   DOMElement  $element
     * @param   array       $context
     */
    private function parseSettings(DOMElement $element, array $context)
    {
        $interpolator = new Interpolator();
        $context = array_merge($context, $this->extractContext($element));
        if ('settings' == $element->localName) {
            $this->settings[] = $interpolator->interpolate($context);
        }
        $this->walkSubNodes($element, $context);
    }

    /**
     * Extracts the local context defined in a settings node.
     *
     * @param   DOMElement  $element
     * @return  array
     */
    private function extractContext(DOMElement $element)
    {
        $context = array();

        // Finds those elements and attributes defined in the default namespace
        $xpathExpression = 'child::*[local-name() = name()] | attribute::*[local-name() = name()]';
        $nodes = $this->query($xpathExpression, $element);
        foreach ($nodes as $node) {
            $key = $node->nodeName;
            $value = $node->nodeValue;
            if (isset($context[$key])) {
                $context[$key] = (array)$context[$key];
                $context[$key][] = $value;
            } else {
                $context[$node->nodeName] = $value;
            }
        }

        return $context;
    }

    /**
     * Performs an XPath query.
     *
     * @param   string      $xpathExpression
     * @param   DOMNode     $context
     * @return  DOMElement[]
     */
    private function query($xpathExpression, DOMNode $context =  null)
    {
        $result = $this->xpath->query($xpathExpression, $context);
        $nodes = array();
        for ($i = 0; $i < $result->length; ++$i) {
            $nodes[] = $result->item($i);
        }
        return $nodes;
    }
}

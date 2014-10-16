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
        $context = array_merge($context, $this->extractContext($element));
        if ('settings' == $element->localName) {
            $this->interpolate($context);
            $this->settings[] = $context;
        }
        $this->walkSubNodes($element, $context);
    }

    /**
     * Interpolates string context values with values of other keys in the context.
     *
     * @param   array   &$context   Context whose variables will be interpolated
     */
    private function interpolate(array &$context)
    {
        $stack = $solvedContext = array();
        foreach ($context as $key => &$value) {
            $value = $this->doInterpolation($key, $context, $stack, $solvedContext);
        }
    }

    /**
     * Auxiliary recursive method. Does the recursive interpolation.
     *
     * @param   string  $key            Current context's key being iterated
     * @param   array   $context        Current context
     * @param   array   $stack          Stack to store the keys that are being interpolated
     * @param   array   $solvedContext  Pseudo-context that stores already resolved interpolations
     * @return  mixed
     */
    private function doInterpolation($key, $context, & $stack, & $solvedContext)
    {
        // Matches things like {{whatever}}, {{ what}ever }}, {{what}e}v}e}r }}...
        // and extracts the string comprised between "{{" and "}}"
        $regex = '/\{\{\s*((?:(?!}})\S)+)\s*}}/';

        if (isset($solvedContext[$key])) {
            return $solvedContext[$key];
        }

        if (in_array($key, $stack)) {
            trigger_error(sprintf('Cyclic recursion: %s -> %s', implode(' -> ', $stack), $key), E_USER_WARNING);
            return $solvedContext[$key] = '';
        }

        if (!isset($context[$key])) {
            trigger_error(sprintf('Undefined key: "%s"', $key), E_USER_WARNING);
            return $solvedContext[$key] = '';
        }

        if (!is_string($context[$key]) || !preg_match($regex, $context[$key])) {
            return $solvedContext[$key] = $context[$key];
        }

        $_this = $this;
        array_push($stack, $key);
        $solvedContext[$key] = preg_replace_callback($regex, function($matches) use ($context, & $stack, & $solvedContext, $_this) {
            list(, $subkey) = $matches;
            $value = $_this->doInterpolation($subkey, $context, $stack, $solvedContext);
            if (is_array($value)) {
                trigger_error(sprintf('Array interpolation: "%s"', $subkey), E_USER_WARNING);
                $value = '<array>';
            }
            return $value;
        }, $context[$key]);
        array_pop($stack);

        return $solvedContext[$key];
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

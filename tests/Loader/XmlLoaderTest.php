<?php

namespace Superruzafa\Settings\Loader;

use DOMDocument;

class XmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @test */
    public function simpleSettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:settings xmlns:s="http://github.com/superruzafa/settings-loader">
    <key1>value1</key1>
    <key2>value2</key2>
</s:settings>
XML;
        $settings = array(
            array(
                'key1' => 'value1',
                'key2' => 'value2',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function abstractSettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:abstract xmlns:s="http://github.com/superruzafa/settings-loader">
    <key1>value1</key1>
    <key2>value2</key2>
</s:abstract>
XML;

        $this->doLoad(array(), $xml);
    }

    /** @test */
    public function concreteSettingsUnderAbstractSettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:abstract xmlns:s="http://github.com/superruzafa/settings-loader" xmlns="http://example.org">
    <key1>foo1</key1>
    <key2>value2</key2>
    <s:settings>
        <key1>value1</key1>
    </s:settings>
</s:abstract>
XML;

        $settings = array(
            array(
                'key1' => 'value1',
                'key2' => 'value2',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function concreteSettingsUnderConcreteSettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:settings xmlns:s="http://github.com/superruzafa/settings-loader" xmlns="http://example.org">
    <key1>value1</key1>
    <s:settings>
        <key2>value2</key2>
    </s:settings>
</s:settings>
XML;

        $settings = array(
            array(
                'key1' => 'value1',
            ),
            array(
                'key1' => 'value1',
                'key2' => 'value2',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function arraySettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:settings xmlns:s="http://github.com/superruzafa/settings-loader" xmlns="http://example.org">
    <key>value1</key>
    <key>value2</key>
</s:settings>
XML;

        $settings = array(
            array(
                'key' => array('value1', 'value2'),
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function overrideArraySettings()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:settings xmlns:s="http://github.com/superruzafa/settings-loader" xmlns="http://example.org">
    <key>value1</key>
    <key>value2</key>
    <s:settings>
        <key>value3</key>
    </s:settings>
</s:settings>
XML;

        $settings = array(
            array(
                'key' => array('value1', 'value2'),
            ),
            array(
                'key' => 'value3',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function settingsInAttributes()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<s:settings
    xmlns:s="http://github.com/superruzafa/settings-loader"
    key1="foo" key2="value2" key3="foo">
    >
    <key3>bar</key3>
    <s:settings key1="value1" key3="value3">
        <key4>value4</key4>
    </s:settings>
</s:settings>
XML;

        $settings = array(
            array('key1' => 'foo', 'key2' => 'value2', 'key3' => array('foo', 'bar')),
            array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4')
        );

        $this->doLoad($settings, $xml);
    }

    /**
     * @test
     * @group interpolation
     */
    public function simpleInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader"
    test1="This is a {{ missing }} string">
    <missing>found</missing>
</bar:settings>
XML;
        $settings = array(
            array(
                'test1' => 'This is a found string',
                'missing' => 'found',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /** @test */
    public function multipleInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader">
    <text1>{{text2}} {{text2}}</text1>
    <text2>{{text3}} {{text4}}</text2>
    <text3>foo</text3>
    <text4>bar</text4>
</bar:settings>
XML;
        $settings = array(
            array(
                'text1' => 'foo bar foo bar',
                'text2' => 'foo bar',
                'text3' => 'foo',
                'text4' => 'bar',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /**
     * @test
     * @group interpolation
     * @group warnings
     */
    public function recursiveInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader">
    <text1>{{text2}}</text1>
    <text2>{{text3}}</text2>
    <text3>The text</text3>
    <text4>Other {{ text2 }}</text4>
    <text5>More text</text5>
</bar:settings>
XML;
        $settings = array(
            array(
                'text1' => 'The text',
                'text2' => 'The text',
                'text3' => 'The text',
                'text4' => 'Other The text',
                'text5' => 'More text',
            )
        );

        $this->doLoad($settings, $xml);
    }

    /**
     * @test
     * @group interpolation
     * @group warnings
     */
    public function unsatisfiedInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader">
    <foo>This is a {{ missing }} string</foo>
</bar:settings>
XML;
        $settings = array(
            array(
                'foo' => 'This is a  string',
            )
        );

        $errors = &$this->captureErrors();
        $this->doLoad($settings, $xml);
        $this->stopCaptureErrors();
        $this->assertEquals(E_USER_WARNING, $errors[0]['code']);
        $this->assertEquals('Undefined key: "missing"', $errors[0]['message']);
    }

    /**
     * @test
     * @group interpolation
     * @group warnings
     */
    public function cyclicRecursiveInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader">
    <text1>1 {{text2}}</text1>
    <text2>2 {{text3}}</text2>
    <text3>3 {{text1}}</text3>
</bar:settings>
XML;
        $settings = array(
            array(
                'text1' => '1 2 3 ',
                'text2' => '2 3 ',
                'text3' => '3 ',
            )
        );

        $errors = &$this->captureErrors();
        $this->doLoad($settings, $xml);
        $this->assertEquals(E_USER_WARNING, $errors[0]['code']);
        $this->assertEquals('Cyclic recursion: text1 -> text2 -> text3 -> text1', $errors[0]['message']);
        $this->stopCaptureErrors();
    }

    /**
     * @test
     * @group interpolation
     * @group warnings
     */
    public function arrayInterpolation()
    {
        $xml = <<< 'XML'
<?xml version="1.0" encoding="UTF-8"?>
<bar:settings
    xmlns:bar="http://github.com/superruzafa/settings-loader">
    <text1>FOO {{ arrayKey }} BAR</text1>
    <arrayKey>1</arrayKey>
    <arrayKey>2</arrayKey>
    <arrayKey>3</arrayKey>
</bar:settings>
XML;
        $settings = array(
            array(
                'text1' => 'FOO <array> BAR',
                'arrayKey' => array(1, 2, 3)
            )
        );

        $errors = &$this->captureErrors();
        $this->doLoad($settings, $xml);
        $this->assertEquals(E_USER_WARNING, $errors[0]['code']);
        $this->assertEquals('Array interpolation: "arrayKey"', $errors[0]['message']);
        $this->stopCaptureErrors();
    }

    private function doLoad($expectedSettings, $xml)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $loader = new XmlLoader($doc);
        $loader->load();
        $this->assertEquals($expectedSettings, $loader->getSettings());
    }

    private function &captureErrors()
    {
        $errors = array();
        set_error_handler(function ($code, $message) use (&$errors) {
            $errors[] = compact('code', 'message');
        });
        return $errors;
    }

    private function stopCaptureErrors()
    {
        restore_error_handler();
    }
}

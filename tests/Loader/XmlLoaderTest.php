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

    protected function doLoad($expectedSettings, $xml)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xml);
        $loader = new XmlLoader($doc);
        $loader->load();
        $this->assertEquals($expectedSettings, $loader->getSettings());
    }
}

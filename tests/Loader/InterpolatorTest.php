<?php

namespace Superruzafa\Settings\Loader;

class InterpolatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Interpolator */
    private $interpolator;

    /** @var array */
    private static $errors;

    public static function setUpBeforeClass()
    {
        $errors = &self::$errors;
        set_error_handler(function ($code, $message) use (&$errors) {
            $errors[] = compact('code', 'message');
        });
    }

    protected function setUp()
    {
        $this->interpolator = new Interpolator();
    }

    protected function tearDown()
    {
        self::$errors = array();
    }

    public static function tearDownAfterClass()
    {
        restore_error_handler();
    }

    /** @test */
    public function emptyContext()
    {
        $this->assertEquals(array(), $this->interpolator->interpolate(array()));
    }

    /** @test */
    public function noInterpolation()
    {
        $context = array(
            'key' => 'value'
        );
        $this->assertEquals($context, $this->interpolator->interpolate($context));
    }

    /** @test */
    public function simpleInterpolation()
    {
        $context = array(
            'key1' => '{{ key2 }}',
            'key2' => 'value2'
        );
        $expected = array(
            'key1' => 'value2',
            'key2' => 'value2'
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
    }

    /** @test */
    public function multipleInterpolation()
    {
        $context = array(
            'key1' => '{{ key2 }} {{ key3 }}',
            'key2' => '{{ key3 }} OK!',
            'key3' => 'OK'
        );
        $expected = array(
            'key1' => 'OK OK! OK',
            'key2' => 'OK OK!',
            'key3' => 'OK'
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
    }

    /** @test */
    public function weirdPatterns()
    {
        $context = array(
            'key1' => '{{key9}} {{ key9 }} {{key9 }} {{ key9}} {{   key9     }}',
            'key2' => '{{ {{ key9 }} }}',
            'key9' => 'X',
        );
        $expected = array(
            'key1' => 'X X X X X',
            'key2' => '{{ X }}',
            'key9' => 'X',
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
    }


    /**
     * @test
     * @group warnings
     */
    public function undefinedKeyInterpolation()
    {
        $context = array(
            'key1' => 'He{{ key2 }}llo',
        );
        $expected = array(
            'key1' => 'Hello',
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
        $this->assertTriggeredError('Undefined key: "key2"', E_USER_WARNING);
    }

    /**
     * @test
     * @group warnings
     */
    public function cyclicRecursiveInterpolation()
    {
        $context = array(
            'key1' => '1 = {{ key2 }}',
            'key2' => '2 = {{ key3 }}',
            'key3' => '3 = {{ key1 }}'
        );
        $expected = array(
            'key1' => '1 = 2 = 3 = ',
            'key2' => '2 = 3 = ',
            'key3' => '3 = '
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
        $this->assertTriggeredError('Cyclic recursion: key1 -> key2 -> key3 -> key1', E_USER_WARNING);
    }

    /**
     * @test
     * @group warnings
     */
    public function arrayInterpolation()
    {
        $context = array(
            'key' => '({{arrayKey}})',
            'arrayKey' => array(1, 2, 3, 4)
        );
        $expected = array(
            'key' => '(<array>)',
            'arrayKey' => array(1, 2, 3, 4)
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
        $this->assertTriggeredError('Array interpolation: "arrayKey"', E_USER_WARNING);
    }

    /**
     * @test
     * @group warnings
     */
    public function objectInterpolation()
    {
        $object = new \stdClass;
        $context = array(
            'key' => '({{objectKey}})',
            'objectKey' => $object,
        );
        $expected = array(
            'key' => '(<object>)',
            'objectKey' => $object,
        );
        $this->assertEquals($expected, $this->interpolator->interpolate($context));
        $this->assertTriggeredError('Object interpolation: "objectKey"', E_USER_WARNING);
    }

    private function assertTriggeredError($message, $code)
    {
        foreach (self::$errors as $error) {
            if ($error['code'] == $code && $error['message'] == $message) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail(sprintf('Fail while asserting that error message "%s" with error code %d was triggered', $message, $code));
    }
}

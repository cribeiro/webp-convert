<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace WebPConvert\Tests\Convert\Converters;

use WebPConvert\Convert\Converters\Ewww;
use PHPUnit\Framework\TestCase;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\InvalidApiKeyException;
use WebPConvert\Tests\Convert\TestConverters\ExtendedConverters\EwwwExtended;

class EwwwTest extends TestCase
{

    public $imageDir = __DIR__ . '/../../images/';

    public function testConvert()
    {
        ConverterTestHelper::runAllConvertTests($this, 'Ewww', [
            //'api-key' => ''
        ]);
    }

    public function testConvertInvalidKeyLessThan20()
    {
        $this->expectException(InvalidApiKeyException::class);

        $source = $this->imageDir . '/test.png';
        Ewww::convert($source, $source . '.webp', [
            'api-key' => 'wrong-key!'
        ]);
    }

    public function testConvertInvalidKeyLess32()
    {
        $this->expectException(InvalidApiKeyException::class);

        $wrongKeyRightLength = 'invalid-key-but-hasright-length';

        $source = $this->imageDir . '/test.png';
        Ewww::convert($source, $source . '.webp', [
            'api-key' => $wrongKeyRightLength
        ]);
    }

    public function testConvertInvalidKeyDuringConversion()
    {
        $this->expectException(InvalidApiKeyException::class);

        $wrongKeyRightLength = 'invalid-key-but-hasright-length';

        $source = $this->imageDir . '/test.png';

        $ee = EwwwExtended::createInstance($source, $source . '.webp', [
            'api-key' => $wrongKeyRightLength
        ]);

        $ee->callDoActualConvert();
    }


    public function testIsValidKey()
    {
        $invalidKey = 'notvalidno';
        $this->assertFalse(Ewww::isValidKey($invalidKey));

        $demoKey = 'abc123';
        $this->assertTrue(Ewww::isValidKey($demoKey));


        //InvalidApiKeyException
    }

    public function testIsWorkingKey()
    {
        $invalidKey = 'notvalidno';
        $this->assertFalse(Ewww::isWorkingKey($invalidKey));

        if (!empty(getenv('EWWW_API_KEY'))) {
            $realWorkingKey = getenv('EWWW_API_KEY');
            $this->assertTrue(Ewww::isWorkingKey($realWorkingKey));
        }
    }
}

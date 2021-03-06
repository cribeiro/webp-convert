<?php
/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace WebPConvert\Tests\Convert\Converters;

use WebPConvert\Convert\Converters\GmagickBinary;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;
use WebPConvert\Loggers\BufferLogger;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass WebPConvert\Convert\Converters\GmagickBinary
 * @covers WebPConvert\Convert\Converters\GmagickBinary
 */
class GmagickBinaryTest extends TestCase
{

    public $imageDir = __DIR__ . '/../../images/';

    public function testConvert()
    {
        ConverterTestHelper::runAllConvertTests($this, 'GmagickBinary');
    }

    private static function tryThis($test, $source, $options)
    {
        $bufferLogger = new BufferLogger();

        try {
            GmagickBinary::convert($source, $source . '.webp', $options, $bufferLogger);

            $test->addToAssertionCount(1);
        } catch (ConversionFailedException $e) {

            //$bufferLogger->getText()
            throw $e;
        } catch (ConverterNotOperationalException $e) {
            // (SystemRequirementsNotMetException is also a ConverterNotOperationalException)
            // this is ok.
            return;
        }
    }

    public function testWithNice() {
        $source = $this->imageDir . '/test.png';
        $options = [
            'use-nice' => true,
            'lossless' => true,
        ];
        self::tryThis($this, $source, $options);
    }

}

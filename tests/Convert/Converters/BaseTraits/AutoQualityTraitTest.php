<?php

namespace WebPConvert\Tests\Convert\Converters\BaseTraits;

//use WebPConvert\Convert\BaseConverters\AbstractCloudConverter;
use WebPConvert\Tests\Convert\TestConverters\SuccessGuaranteedConverter;

use PHPUnit\Framework\TestCase;

class AutoQualityTraitTest extends TestCase
{

    private static $imgDir = __DIR__ . '/../../../images';

    public function testFixedQuality()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/small-q61.jpg',
            self::$imgDir . '/small-q61.jpg.webp',
            [
                'max-quality' => 80,
                'quality' => 75,
                'default-quality' => 70,
            ]
        );

        $result = $converter->getCalculatedQuality();
        $this->assertSame(75, $result);

        $this->assertFalse($converter->isQualityDetectionRequiredButFailing());

        // Test that it is still the same (testing caching)
        $this->assertFalse($converter->isQualityDetectionRequiredButFailing());

    }

/*
    public function testAutoQualityWhenQualityCannotBeDetected()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/non-existant',
            self::$imgDir . '/non-existant.webp',
            [
                'max-quality' => 80,
                'quality' => 'auto',
                'default-quality' => 70,
            ]
        );

        $result = $converter->getCalculatedQuality();

        $this->assertSame(70, $result);
    }*/

    public function testAutoQuality()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/small-q61.jpg',
            self::$imgDir . '/small-q61.jpg.webp',
            [
                'max-quality' => 80,
                'quality' => 'auto',
                'default-quality' => 61,
            ]
        );

        $result = $converter->getCalculatedQuality();

        // "Cheating" a bit here...
        // - If quality detection fails, it will be 61 (because default-quality is set to 61)
        // - If quality detection succeeds, it will also be 61
        $this->assertSame(61, $result);
    }

    public function testAutoQualityMaxQuality()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/small-q61.jpg',
            self::$imgDir . '/small-q61.jpg.webp',
            [
                'max-quality' => 60,
                'quality' => 'auto',
                'default-quality' => 61,
            ]
        );

        //$this->assertTrue(file_exists(self::$imgDir . '/small-q61.jpg'));
        //$this->assertEquals('image/jpeg', $converter->getMimeTypeOfSource());

        $this->assertSame(60, $converter->getCalculatedQuality());

        // Test that it is still the same (testing caching)
        $this->assertSame(60, $converter->getCalculatedQuality());
    }

    public function testAutoQualityMaxQualityOnNonJpeg()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/test.png',
            self::$imgDir . '/test.png.webp',
            [
                'max-quality' => 60,
                'quality' => 'auto',
                'default-quality' => 70,
            ]
        );

        $this->assertSame(60, $converter->getCalculatedQuality());
        $this->assertFalse($converter->isQualityDetectionRequiredButFailing());
    }
/*
    public function testAutoQualityOnQualityDetectionFail1()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/non-existing.jpg',
            self::$imgDir . '/non-existant.webp',
            [
                'max-quality' => 70,
                'quality' => 'auto',
                'default-quality' => 60,
            ]
        );

        $this->assertFalse(file_exists(self::$imgDir . '/non-existing.jpg'));

        // MimeType guesser returns false when mime type cannot be established.
        $this->assertEquals(false, $converter->getMimeTypeOfSource());

        // - so this can actually not be used for testing isQualityDetectionRequiredButFailing

        //$this->assertSame(60, $converter->getCalculatedQuality());
        //$this->assertTrue($converter->isQualityDetectionRequiredButFailing());
    }
*/
    public function testAutoQualityOnQualityDetectionFail2()
    {
        $converter = SuccessGuaranteedConverter::createInstance(
            self::$imgDir . '/text-with-jpg-extension.jpg',
            self::$imgDir . '/text-with-jpg-extension.jpg.webp',
            [
                'max-quality' => 70,
                'quality' => 'auto',
                'default-quality' => 60,
            ]
        );

        $this->assertFalse(file_exists(self::$imgDir . '/non-existing.jpg'));

        // We are using the lenient MimeType guesser.
        // So we get "image/jpeg" even though the file is not a jpeg file
        $this->assertEquals('image/jpeg', $converter->getMimeTypeOfSource());

        // Now we got a file that we should not be able to detect quality of
        // lets validate that statement:

        $this->assertTrue($converter->isQualityDetectionRequiredButFailing());

        // Test that it is still the same (testing caching)
        $this->assertTrue($converter->isQualityDetectionRequiredButFailing());

        $this->assertSame(60, $converter->getCalculatedQuality());
    }

}

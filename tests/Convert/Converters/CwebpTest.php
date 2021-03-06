<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace WebPConvert\Tests\Convert\Converters;

use WebPConvert\Convert\Converters\Cwebp;
use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;

use WebPConvert\Tests\Convert\Exposers\CwebpExposer;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass WebPConvert\Convert\Converters\Cwebp
 * @covers WebPConvert\Convert\Converters\Cwebp
 */
class CwebpTest extends TestCase
{

    public static $imageDir = __DIR__ . '/../..';

    public function testConvert()
    {
        ConverterTestHelper::runAllConvertTests($this, 'Cwebp');
    }

    public function testSource()
    {
        $source = self::$imageDir . '/test.png';
        $cwebp = new Cwebp($source, $source . '.webp');
        $cwebpExposer = new CwebpExposer($cwebp);

        $this->assertEquals($source, $cwebpExposer->getSource());
        $this->assertTrue(file_exists($source), 'source does not exist');
    }

    /**
     * @covers ::createCommandLineOptions
     */
    public function testCreateCommandLineOptions()
    {
        $source = self::$imageDir . '/test.png';
        $options = [
            'quality' => 'auto',
            'method' => 3,
            'command-line-options' => '-sharpness 5 -crop 10 10 40 40 -low_memory',
        ];
        $cwebp = new Cwebp($source, $source . '.webp', $options);
        $cwebpExposer = new CwebpExposer($cwebp);

        //$cwebpExposer->prepareOptions();

        $commandLineOptions = $cwebpExposer->createCommandLineOptions();
        //$this->assertEquals('e', $commandLineOption); // use this to quickly see it...

        // Per default we have no preset set
        $this->assertNotRegExp('#-preset#', $commandLineOptions);

        // Metadata is per default none
        $this->assertRegExp('#-metadata none#', $commandLineOptions);

        // We passed the method option and set it to 3
        $this->assertRegExp('#-m 3#', $commandLineOptions);

        // There must be an output option, and it must be quoted
        $this->assertRegExp('#-o \'#', $commandLineOptions);

        // There must be a quality option, and it must be digits
        $this->assertRegExp('#-q \\d+#', $commandLineOptions);

        // -sharpness '5'
        $this->assertRegExp('#-sharpness \'5\'#', $commandLineOptions);

        // Extra command line option with multiple values. Each are escapeshellarg'ed
        $this->assertRegExp('#-crop \'10\' \'10\' \'40\' \'40\'#', $commandLineOptions);

        // Command line option (flag)
        $this->assertRegExp('#-low_memory#', $commandLineOptions);

        // -sharpness '5'
        $this->assertRegExp('#-sharpness \'5\'#', $commandLineOptions);
    }

    /**
     * @covers ::createCommandLineOptions
     */
    public function testCreateCommandLineOptions2()
    {
        $source = self::$imageDir . '/test.png';
        $options = [
            'quality' => 70,
            'method' => 3,
            'size-in-percentage' => 55,
            'preset' => 'picture'
        ];
        $cwebp = new Cwebp($source, $source . '.webp', $options);
        $cwebpExposer = new CwebpExposer($cwebp);

        //$cwebpExposer->prepareOptions();

        $commandLineOptions = $cwebpExposer->createCommandLineOptions();

        // Preset
        $this->assertRegExp('#-preset picture#', $commandLineOptions);

        // Size
        $fileSizeInBytes = floor($options['size-in-percentage']/100 * filesize($source));
        $this->assertEquals(1714, $fileSizeInBytes);
        $this->assertRegExp('#-size ' . $fileSizeInBytes . '#', $commandLineOptions);

        // There must be no quality option, because -size overrules it.
        $this->assertNotRegExp('#-q \\d+#', $commandLineOptions);
    }

    /**
     * @covers ::createCommandLineOptions
     */
    public function testCreateCommandLineOptions3()
    {
        $source = self::$imageDir . '/test.png';
        $options = [
            'lossless' => true,
            'near-lossless' => 75,
            'autofilter' => true,
        ];
        $cwebp = new Cwebp($source, $source . '.webp', $options);
        $cwebpExposer = new CwebpExposer($cwebp);

        $commandLineOptions = $cwebpExposer->createCommandLineOptions();

        // near-lossless
        $this->assertRegExp('#-near_lossless 75#', $commandLineOptions);

        // There must be no -lossless option, because -near-lossless overrules it.
        $this->assertNotRegExp('#-lossless#', $commandLineOptions);

        // autofilter
        $this->assertRegExp('#-af#', $commandLineOptions);

        // no low-memory
        $this->assertNotRegExp('#-low_memory#', $commandLineOptions);
    }

    /**
     * @covers ::createCommandLineOptions
     */
    public function testCreateCommandLineOptions4()
    {
        $source = self::$imageDir . '/test.png';
        $options = [
            'lossless' => true,
            'near-lossless' => 100,
            'low-memory' => true,
        ];
        $cwebp = new Cwebp($source, $source . '.webp', $options);
        $cwebpExposer = new CwebpExposer($cwebp);

        $commandLineOptions = $cwebpExposer->createCommandLineOptions();

        // lossless
        $this->assertRegExp('#-lossless#', $commandLineOptions);

        // There must be no -near_lossless option, because -lossless overrules it.
        $this->assertNotRegExp('#-near_lossless#', $commandLineOptions);

        // low-memory
        $this->assertRegExp('#-low_memory#', $commandLineOptions);

        // No autofilter
        $this->assertNotRegExp('#-af#', $commandLineOptions);
    }

    /**
     * @covers ::checkOperationality
     */
     public function testOperatinalityException()
     {
         $source = self::$imageDir . '/test.png';
         $options = [
             'try-supplied-binary-for-os' => false,
             'try-common-system-paths' => false,
         ];
         $this->expectException(ConverterNotOperationalException::class);
         //$cwebp = new Cwebp($source, $source . '.webp', $options);
         Cwebp::convert($source, $source . '.webp', $options);
     }

     public function testUsingSuppliedBinaryForOS()
     {
         $source = self::$imageDir . '/test.png';
         $options = [
             'try-supplied-binary-for-os' => true,
             'try-common-system-paths' => false,
         ];
         //$this->expectException(ConverterNotOperationalException::class);
         //$cwebp = new Cwebp($source, $source . '.webp', $options);
         try {
             Cwebp::convert($source, $source . '.webp', $options);
         } catch (ConversionFailedException $e) {
             // this is ok.
             // - but other exceptions are not!
         }
         $this->addToAssertionCount(1);

     }

  /*
    public function testCwebpDefaultPaths()
    {
        $default = [
            '/usr/bin/cwebp',
            '/usr/local/bin/cwebp',
            '/usr/gnu/bin/cwebp',
            '/usr/syno/bin/cwebp'
        ];

        foreach ($default as $key) {
            $this->assertContains($key, Cwebp::$cwebpDefaultPaths);
        }
    }*/

    /**
     * @expectedException \Exception
     */
     /*
    public function testUpdateBinariesInvalidFile()
    {
        $array = [];

        Cwebp::updateBinaries('InvalidFile', 'Hash', $array);
    }*/

    /**
     * @expectedException \Exception
     */
     /*
    public function testUpdateBinariesInvalidHash()
    {
        $array = [];

        Cwebp::updateBinaries('cwebp-linux', 'InvalidHash', $array);
    }

    public function testUpdateBinaries()
    {
        $file = 'cwebp.exe';
        $filePath = realpath(__DIR__ . '/../../Converters/Binaries/' . $file);
        $hash = hash_file('sha256', $filePath);
        $array = [];

        $this->assertContains($filePath, Cwebp::updateBinaries($file, $hash, $array));
    }

    public function testEscapeFilename()
    {
        $wrong = '/path/to/file Na<>me."ext"';
        $right = '/path/to/file\\\ Name.\&#34;ext\&#34;';

        $this->assertEquals($right, Cwebp::escapeFilename($wrong));
    }

    public function testHasNiceSupport()
    {
        $this->assertNotNull(Cwebp::hasNiceSupport());
    }*/
/*
    public function testConvert()
    {
        $source = realpath(__DIR__ . '/../test.jpg');
        $destination = realpath(__DIR__ . '/../test.webp');
        $quality = 85;
        $stripMetadata = true;

        $this->assertTrue(Cwebp::convert($source, $destination, $quality, $stripMetadata));
    }*/

}

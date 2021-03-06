<?php

// TODO: Quality option

namespace WebPConvert\Convert\Converters;

use WebPConvert\Convert\Converters\AbstractConverter;
use WebPConvert\Convert\Exceptions\ConversionFailed\InvalidInput\ConverterNotFoundException;
use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConversionSkippedException;

//use WebPConvert\Convert\Exceptions\ConversionFailed\InvalidInput\TargetNotFoundException;

/**
 * Convert images to webp by trying a stack of converters until success.
 *
 * @package    WebPConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class Stack extends AbstractConverter
{

    protected function getOptionDefinitionsExtra()
    {
        return [
            [
                'converters',
                'array', [
                    'cwebp', 'vips', 'wpc', 'imagickbinary', 'ewww', 'imagick', 'gmagick', 'gmagickbinary', 'gd'
                ],
                true
            ],
            ['shuffle', 'boolean', false],
            ['preferred-converters', 'array', []],
            ['extra-converters', 'array', []]
        ];
    }

    public static $availableConverters = ['cwebp', 'gd', 'imagick', 'gmagick', 'imagickbinary', 'wpc', 'ewww'];
    public static $localConverters = ['cwebp', 'gd', 'imagick', 'gmagick', 'imagickbinary', 'gmagickbinary'];

    public static function converterIdToClassname($converterId)
    {
        switch ($converterId) {
            case 'imagickbinary':
                $classNameShort = 'ImagickBinary';
                break;
            case 'gmagickbinary':
                $classNameShort = 'GmagickBinary';
                break;
            default:
                $classNameShort = ucfirst($converterId);
        }
        $className = 'WebPConvert\\Convert\\Converters\\' . $classNameShort;
        if (is_callable([$className, 'convert'])) {
            return $className;
        } else {
            throw new ConverterNotFoundException('There is no converter with id:' . $converterId);
        }
    }

    public static function getClassNameOfConverter($converterId)
    {
        if (strtolower($converterId) == $converterId) {
            return self::converterIdToClassname($converterId);
        }
        $className = $converterId;
        if (!is_callable([$className, 'convert'])) {
            throw new ConverterNotFoundException('There is no converter with class name:' . $className);
        }

        return $className;
    }

    /**
     * Check (general) operationality of imagack converter executable
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met
     */
    public function checkOperationality()
    {
        if (count($this->options['converters']) == 0) {
            throw new ConverterNotOperationalException(
                'Converter stack is empty! - no converters to try, no conversion can be made!'
            );
        }

        // TODO: We should test if all converters are found in order to detect problems early

        $this->logLn('Stack converter ignited');
    }

    protected function doActualConvert()
    {
        $options = $this->options;

        $beginTimeStack = microtime(true);


        // If we have set converter options for a converter, which is not in the converter array,
        // then we add it to the array
        /*
        if (isset($options['converter-options'])) {
            foreach ($options['converter-options'] as $converterName => $converterOptions) {
                if (!in_array($converterName, $converters)) {
                    $converters[] = $converterName;
                }
            }
        }*/




        $anyRuntimeErrors = false;

        $converters = $options['converters'];
        if (count($options['extra-converters']) > 0) {
            $converters = array_merge($converters, $options['extra-converters']);
            /*foreach ($options['extra-converters'] as $extra) {
                $converters[] = $extra;
            }*/
        }

        // preferred-converters
        if (count($options['preferred-converters']) > 0) {
            foreach (array_reverse($options['preferred-converters']) as $prioritizedConverter) {
                foreach ($converters as $i => $converter) {
                    if (is_array($converter)) {
                        $converterId = $converter['converter'];
                    } else {
                        $converterId = $converter;
                    }
                    if ($converterId == $prioritizedConverter) {
                        unset($converters[$i]);
                        array_unshift($converters, $converter);
                        break;
                    }
                }
            }
            // perhaps write the order to the log? (without options) - but this requires some effort
        }

        // shuffle
        if ($options['shuffle']) {
            shuffle($converters);
        }

        //$this->logLn(print_r($converters));
        //$options['converters'] = $converters;
        $defaultConverterOptions = $options;

        //unset($defaultConverterOptions['converters']);
        //unset($defaultConverterOptions['converter-options']);
        $defaultConverterOptions['_skip_input_check'] = true;
        $defaultConverterOptions['_suppress_success_message'] = true;
        unset($defaultConverterOptions['converters']);
        unset($defaultConverterOptions['extra-converters']);
        unset($defaultConverterOptions['converter-options']);
        unset($defaultConverterOptions['preferred-converters']);

//        $this->logLn('converters: ' . print_r($converters, true));

        //return;
        foreach ($converters as $converter) {
            if (is_array($converter)) {
                $converterId = $converter['converter'];
                $converterOptions = $converter['options'];
            } else {
                $converterId = $converter;
                $converterOptions = [];
                if (isset($options['converter-options'][$converterId])) {
                    // Note: right now, converter-options are not meant to be used,
                    //       when you have several converters of the same type
                    $converterOptions = $options['converter-options'][$converterId];
                }
            }
            $converterOptions = array_merge($defaultConverterOptions, $converterOptions);
            /*
            if ($converterId != 'stack') {
                //unset($converterOptions['converters']);
                //unset($converterOptions['converter-options']);
            } else {
                //$converterOptions['converter-options'] =
                $this->logLn('STACK');
                $this->logLn('converterOptions: ' . print_r($converterOptions, true));
            }*/

            $beginTime = microtime(true);

            $className = self::getClassNameOfConverter($converterId);


            try {
                $converterDisplayName = call_user_func(
                    [$className, 'getConverterDisplayName']
                );
            } catch (\Exception $e) {
                // TODO: handle failure better than this
                $converterDisplayName = 'Untitled converter';
            }

            try {
                $this->ln();
                $this->logLn('Trying: ' . $converterId, 'italic');

                call_user_func(
                    [$className, 'convert'],
                    $this->source,
                    $this->destination,
                    $converterOptions,
                    $this->logger
                );

                //self::runConverterWithTiming($converterId, $source, $destination, $converterOptions, false, $logger);

                $this->logLn($converterDisplayName . ' succeeded :)');
                //throw new ConverterNotOperationalException('...');
                return;
            } catch (ConverterNotOperationalException $e) {
                $this->logLn($e->getMessage());
            } catch (ConversionFailedException $e) {
                $this->logLn($e->getMessage(), 'italic');
                $prev = $e->getPrevious();
                if (!is_null($prev)) {
                    $this->logLn($prev->getMessage(), 'italic');
                    $this->logLn(' in ' . $prev->getFile() . ', line ' . $prev->getLine(), 'italic');
                    $this->ln();
                }
                //$this->logLn($e->getTraceAsString());
                $anyRuntimeErrors = true;
            } catch (ConversionSkippedException $e) {
                $this->logLn($e->getMessage());
            }

            $this->logLn($converterDisplayName . ' failed in ' . round((microtime(true) - $beginTime) * 1000) . ' ms');
        }

        $this->ln();
        $this->logLn('Stack failed in ' . round((microtime(true) - $beginTimeStack) * 1000) . ' ms');

        if ($anyRuntimeErrors) {
            // At least one converter failed
            throw new ConversionFailedException(
                'None of the converters in the stack could convert the image. ' .
                'At least one failed, even though its requirements seemed to be met.'
            );
        } else {
            // All converters threw a SystemRequirementsNotMetException
            throw new ConverterNotOperationalException('None of the converters in the stack are operational');
        }
    }
}

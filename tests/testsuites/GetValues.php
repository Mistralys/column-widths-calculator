<?php

use Mistralys\WidthsCalculator\Calculator;

class GetValuesTestCase extends CalculatorTestCase
{
    public function test_getValues()
    {
        $tests = array(
            array(
                'label' => 'Single column specified',
                'values' => array(
                    'one' => 14,
                    'two' => 0,
                    'three' => 0
                ),
                'expected' => array(
                    'one' => 14,
                    'two' => 43,
                    'three' => 43
                )
            ),
            array(
                'label' => 'No columns specified',
                'values' => array(
                    'one' => 0,
                    'two' => 0,
                    'three' => 0
                ),
                'expected' => array(
                    'one' => 33,
                    'two' => 33,
                    'three' => 34
                )
            ),
            array(
                'label' => 'Single column out of bounds',
                'values' => array(
                    'one' => 450,
                    'two' => 0,
                    'three' => 0
                ),
                'expected' => array(
                    'one' => 98,
                    'two' => 1,
                    'three' => 1
                )
            ),
            array(
                'label' => 'Several columns, matching 100%',
                'values' => array(
                    'one' => 20,
                    'two' => 30,
                    'three' => 50
                ),
                'expected' => array(
                    'one' => 20,
                    'two' => 30,
                    'three' => 50
                )
            ),
            array(
                'label' => 'Several columns, below 100%',
                'values' => array(
                    'one' => 19,
                    'two' => 29,
                    'three' => 50
                ),
                // leftover percentages are filled from the last one upwards
                'expected' => array(
                    'one' => 19,
                    'two' => 30,
                    'three' => 51
                )
            ),
            array(
                'label' => 'Above 100%, all columns filled',
                'values' => array(
                    'one' => 1400,
                    'two' => 900,
                    'three' => 700
                ),
                // leftover percentages are filled from the last one upwards
                'expected' => array(
                    'one' => 38,
                    'two' => 33,
                    'three' => 29
                )
            ),
            array(
                'label' => 'Above 100%, several empty columns',
                'values' => array(
                    'one' => 1400,
                    'two' => 900,
                    'three' => 0,
                    'four' => 0
                ),
                'expected' => array(
                    'one' => 30,
                    'two' => 19,
                    'three' => 25,
                    'four' => 26
                )
            ),
            array(
                'label' => 'Above 100%, single filled column',
                'values' => array(
                    'one' => 1400,
                    'two' => 0,
                    'three' => 0
                ),
                'expected' => array(
                    'one' => 98,
                    'two' => 1,
                    'three' => 1
                )
            ),
            array(
                'label' => 'Single column array, below 100%',
                'values' => array(
                    'one' => 80
                ),
                'expected' => array(
                    'one' => 100
                )
            ),
            array(
                'label' => 'Single column array, above 100%',
                'values' => array(
                    'one' => 8045
                ),
                'expected' => array(
                    'one' => 100
                )
            )
        );
        
        foreach($tests as $test)
        {
            $result = Calculator::create($test['values'])->getValues();
            
            $values = array_values($result);
            $total = array_sum($values);
            
            $this->assertEquals(100, $total, $test['label']);
            $this->assertEquals($test['expected'], $result, $test['label']);
        }
    }
}

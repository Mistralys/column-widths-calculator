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
                    'one' => 33,
                    'two' => 33,
                    'three' => 34
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
            )
        );
        
        foreach($tests as $test)
        {
            $result = Calculator::create($test['values'])->getValues();

            $this->assertEquals($test['expected'], $result, $test['label']);
        }
    }
}

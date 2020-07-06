<?php

use Mistralys\WidthsCalculator\Calculator;

class FloatValuesTestCase extends CalculatorTestCase
{
    public function test_floatValues()
    {
        $values = array(
            'col1' => 60.78,
            'col2' => 12.13,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        $calc->setFloatValues();
        
        $expected = array(
            'col1' => 60.78,
            'col2' => 12.13,
            'col3' => 27.09
        );
        
        $this->assertEquals($expected, $calc->getValues());
    }
}

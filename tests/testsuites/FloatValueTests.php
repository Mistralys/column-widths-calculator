<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;

class FloatValueTests extends CalculatorTestCase
{
    public function test_floatValues() : void
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

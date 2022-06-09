<?php

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;

class PixelValueTests extends CalculatorTestCase
{
    public function test_getPixelValues() : void
    {
        $pixelValue = 600;
        
        $columns = array(
            'Col1' => 40,
            'Col2' => 40,
            'Col3' => 20,
        );
        
        $calc = Calculator::create($columns);
        
        $result = $calc->getPixelValues($pixelValue);
        
        $expected = array(
            'Col1' => 207,
            'Col2' => 207,
            'Col3' => 186
        );
        
        $this->assertEquals($pixelValue, array_sum(array_values($expected)));
        $this->assertEquals($expected, $result);
    }
}

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
        $this->assertTotalEquals(100.0, $calc->getValues()); // H6
    }

    /**
     * H4 — Float mode with overflow: values must be floats summing to exactly 100.0.
     */
    public function test_floatValues_overflow(): void
    {
        $calc = Calculator::create(['A' => 500, 'B' => 300]);
        $calc->setFloatValues();
        $result = $calc->getValues();

        $this->assertTotalEquals(100.0, $result);

        foreach ($result as $value) {
            $this->assertIsFloat($value);
        }
    }

    /**
     * H5 — Float mode with surplus removal via minWidth: total must equal 100.0.
     */
    public function test_floatValues_surplus(): void
    {
        $calc = Calculator::create(['A' => 80, 'B' => 20, 'C' => 0]);
        $calc->setFloatValues();
        $calc->setMinWidth(20);
        $result = $calc->getValues();

        $this->assertTotalEquals(100.0, $result);
    }

    /**
     * M7 — Float mode with all columns missing: total must equal 100.0
     * (within floating-point tolerance, as equal redistribution of 3 columns
     * introduces sub-epsilon imprecision).
     */
    public function test_floatValues_allMissing(): void
    {
        $calc = Calculator::create(['A' => 0, 'B' => 0, 'C' => 0]);
        $calc->setFloatValues();
        $result = $calc->getValues();

        $this->assertEqualsWithDelta(100.0, array_sum(array_values($result)), 0.001);
    }
}

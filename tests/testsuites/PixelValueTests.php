<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;

class PixelValueTests extends CalculatorTestCase
{
    public function test_getPixelValues(): void
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

    /**
     * M1 — getPixelValues() with missing columns: sum must equal targetWidth.
     */
    public function test_getPixelValues_missingColumns(): void
    {
        $calc = Calculator::create(['A' => 40, 'B' => 0, 'C' => 0]);
        $result = $calc->getPixelValues(600);

        $this->assertEquals(600, array_sum(array_values($result)));
    }

    /**
     * M2 — getPixelValues() with custom maxTotal: sum must equal targetWidth.
     */
    public function test_getPixelValues_customMaxTotal(): void
    {
        $calc = Calculator::create(['A' => 100, 'B' => 60, 'C' => 40]);
        $calc->setMaxTotal(200);
        $result = $calc->getPixelValues(800);

        $this->assertEquals(800, array_sum(array_values($result)));
    }

    /**
     * M3 — getPixelValues() with small targetWidth: sum must equal targetWidth
     * and all values must be >= 0.
     */
    public function test_getPixelValues_smallTargetWidth(): void
    {
        $calc = Calculator::create(['A' => 20, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 20]);
        $result = $calc->getPixelValues(10);

        $this->assertEquals(10, array_sum(array_values($result)));
        foreach ($result as $value) {
            $this->assertGreaterThanOrEqual(0, $value);
        }
    }
}

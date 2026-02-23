<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use InvalidArgumentException;
use Mistralys\WidthsCalculator\Calculator;

class MinWidthTests extends CalculatorTestCase
{
    public function test_setMinWidth(): void
    {
        $values = array(
            'col1' => 100,
            'col2' => 0,
            'col3' => 0
        );

        $calc = Calculator::create($values);
        $calc->setMinWidth(20);

        $expected = array(
            'col1' => 60,
            'col2' => 20,
            'col3' => 20
        );

        $this->assertEquals($expected, $calc->getValues());
    }

    public function test_setMinWidth_surplus(): void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );

        $calc = Calculator::create($values);
        $calc->setMinWidth(20);

        // surplus is removed proportionally from
        // existing columns.
        $expected = array(
            'col1' => 60,
            'col2' => 20,
            'col3' => 20
        );

        $this->assertEquals($expected, $calc->getValues());
    }

    public function test_surplus(): void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );

        $calc = Calculator::create($values);

        // surplus is removed proportionally from
        // existing columns.
        $expected = array(
            'col1' => 79,
            'col2' => 20,
            'col3' => 1
        );

        $this->assertEquals($expected, $calc->getValues());
    }

    public function test_surplus_alternate(): void
    {
        $values = array(
            'col1' => 70,
            'col2' => 30,
            'col3' => 0
        );

        $calc = Calculator::create($values);
        $calc->setMinWidth(20);

        // surplus is removed proportionally from
        // existing columns.
        $expected = array(
            'col1' => 52,
            'col2' => 28,
            'col3' => 20
        );

        $this->assertEquals($expected, $calc->getValues());
    }

    public function test_maxMinWidth(): void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );

        $calc = Calculator::create($values);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(Calculator::ERROR_INVALID_MIN_WIDTH); // H8

        $calc->setMinWidth(35); // max is 33.3333
    }

    /**
     * H7 — SurplusRemover depth guard: 9 zero-value columns + 1 large column
     * with minWidth set to the maximum valid value must not recurse infinitely
     * and must produce a result summing to 100.
     */
    public function test_surplusRemover_manyColumnsAtMinWidth(): void
    {
        // 9 columns at 0, 1 column at 100 → heavy surplus after minWidth redistribution
        $input = array_fill(0, 9, 0);
        $input[] = 100;
        $keys = range('A', 'J');
        $input = array_combine($keys, $input);

        $calc = Calculator::create($input);
        $calc->setMinWidth(9); // 10 cols × 9 = 90 ≤ 100 maxTotal: valid

        $result = $calc->getValues();

        $this->assertEquals(100, array_sum(array_values($result)));
    }

    /**
     * M4 — setMinWidth() boundary values: 0, 1, and getMaxMinWidth() must not throw.
     */
    public function test_setMinWidth_boundary(): void
    {
        // 0 is a valid min width (no minimum)
        $calc = Calculator::create(['A' => 50, 'B' => 50]);
        $calc->setMinWidth(0);
        $this->assertEquals(100, array_sum(array_values($calc->getValues())));

        // 1 is the default and always valid
        $calc2 = Calculator::create(['A' => 50, 'B' => 50]);
        $calc2->setMinWidth(1);
        $this->assertEquals(100, array_sum(array_values($calc2->getValues())));

        // getMaxMinWidth() itself — maxTotal (100) / 2 columns = 50 — must not throw
        $calc3 = Calculator::create(['A' => 50, 'B' => 50]);
        $calc3->setMinWidth((int)$calc3->getMaxMinWidth());
        $this->assertEquals(100, array_sum(array_values($calc3->getValues())));
    }

    /**
     * M5 — getMaxMinWidth() direct assertion for 2, 3, and 5 columns.
     */
    public function test_getMaxMinWidth(): void
    {
        // 2 columns: 100 / 2 = 50
        $calc2 = Calculator::create(['A' => 50, 'B' => 50]);
        $this->assertEquals(50.0, $calc2->getMaxMinWidth());

        // 3 columns: 100 / 3 ≈ 33.333...
        $calc3 = Calculator::create(['A' => 33, 'B' => 33, 'C' => 34]);
        $this->assertEqualsWithDelta(33.333, $calc3->getMaxMinWidth(), 0.001);

        // 5 columns: 100 / 5 = 20
        $calc5 = Calculator::create(['A' => 20, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 20]);
        $this->assertEquals(20.0, $calc5->getMaxMinWidth());
    }
}

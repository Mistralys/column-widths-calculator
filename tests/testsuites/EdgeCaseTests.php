<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;

class EdgeCaseTests extends CalculatorTestCase
{
    /**
     * H2 — Negative input values must be treated as missing (zero) columns
     * and receive a positive redistributed width.
     */
    public function test_negativeInputTreatedAsMissing(): void
    {
        $calc = Calculator::create(['A' => -10, 'B' => 50, 'C' => 0]);
        $result = $calc->getValues();

        $this->assertEquals(100, array_sum(array_values($result)));
        $this->assertGreaterThan(0, $result['A']); // negative treated as missing → gets positive width
    }

    /**
     * H3b — setMinWidth() on an empty column array must throw \InvalidArgumentException
     * with code Calculator::ERROR_EMPTY_COLUMN_ARRAY (61502).
     */
    public function testSetMinWidthOnEmptyArrayThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(Calculator::ERROR_EMPTY_COLUMN_ARRAY);

        Calculator::create([])->setMinWidth(5);
    }

    /**
     * H3 — Empty input must throw \InvalidArgumentException with code
     * Calculator::ERROR_EMPTY_COLUMN_ARRAY (61502) before any calculation
     * pipeline is invoked.
     */
    public function test_emptyColumnArray_throwsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(Calculator::ERROR_EMPTY_COLUMN_ARRAY);

        Calculator::create([])->getValues();
    }

    /**
     * H9 — getValues() must be idempotent: repeated calls must return the
     * same values (the single-pass result is cached after the first call).
     */
    public function test_getValuesIdempotency(): void
    {
        $calc = Calculator::create(['A' => 40, 'B' => 30, 'C' => 30]);
        $first  = $calc->getValues();
        $second = $calc->getValues();

        $this->assertSame($first, $second); // strict type+value equality on every element — arrays are value types in PHP
    }

    /**
     * M9 — Numeric keys must be preserved in the result array.
     */
    public function test_numericKeyPreservation(): void
    {
        /** @phpstan-ignore argument.type (integer keys are intentional: testing numeric key preservation) */
        $calc = Calculator::create([0 => 50, 1 => 50]);
        $result = $calc->getValues();

        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals(100, array_sum(array_values($result)));
    }

    /**
     * L3 — Many-column stress test: 15 columns with mixed zero/non-zero values
     * must produce output that sums to 100 with all values > 0.
     */
    public function test_manyColumns(): void
    {
        // 15 columns: mix of 0 and non-zero values
        $input = [
            'A' => 10, 'B' => 0,  'C' => 15, 'D' => 0,  'E' => 8,
            'F' => 0,  'G' => 12, 'H' => 0,  'I' => 5,  'J' => 0,
            'K' => 0,  'L' => 7,  'M' => 0,  'N' => 3,  'O' => 0,
        ];

        $calc = Calculator::create($input);
        $result = $calc->getValues();

        $this->assertEquals(100, array_sum(array_values($result)));
        $this->assertCount(15, $result);

        foreach ($result as $value) {
            $this->assertGreaterThan(0, $value);
        }
    }
}

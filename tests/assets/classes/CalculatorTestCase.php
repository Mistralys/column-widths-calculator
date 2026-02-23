<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;
use PHPUnit\Framework\TestCase;

abstract class CalculatorTestCase extends TestCase
{
    /**
     * @param array<array-key, int|float> $values
     */
    protected function assertTotalEquals(int|float $expected, array $values, ?float $delta = null): void
    {
        $actual = array_sum(array_values($values));
        if ($delta !== null) {
            $this->assertEqualsWithDelta($expected, $actual, $delta);
        } else {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @param array<array-key, float> $input
     * @param array<string, int|float> $expectedOutput
     */
    protected function assertCalculation(
        array $input,
        array $expectedOutput,
        int|float|null $maxTotal = null,
        int|float|null $minWidth = null,
        bool $floatMode = false
    ): void {
        $calc = Calculator::create($input);

        if ($maxTotal !== null) {
            $calc->setMaxTotal($maxTotal);
        }
        if ($minWidth !== null) {
            $calc->setMinWidth($minWidth);
        }
        if ($floatMode) {
            $calc->setFloatValues();
        }

        $result = $calc->getValues();

        $this->assertEquals($expectedOutput, $result);
        $this->assertEquals($maxTotal ?? 100, array_sum(array_values($result)));
    }
}

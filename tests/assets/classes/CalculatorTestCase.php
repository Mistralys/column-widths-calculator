<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;
use PHPUnit\Framework\TestCase;

abstract class CalculatorTestCase extends TestCase
{
    protected function assertTotalEquals(int|float $expected, array $values): void
    {
        $this->assertSame($expected, array_sum(array_values($values)));
    }

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

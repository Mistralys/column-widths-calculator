<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use PHPUnit\Framework\Attributes\DataProvider;

class GetValueTests extends CalculatorTestCase
{
    /**
     * @return array<string, array{array<string, float>, array<string, int>}>
     */
    public static function provideGetValuesCases(): array
    {
        return [
            'single-column-specified' => [
                ['one' => 14.0, 'two' => 0.0, 'three' => 0.0],
                ['one' => 14, 'two' => 43, 'three' => 43],
            ],
            'no-columns-specified' => [
                ['one' => 0.0, 'two' => 0.0, 'three' => 0.0],
                ['one' => 33, 'two' => 33, 'three' => 34],
            ],
            'single-column-out-of-bounds' => [
                ['one' => 450.0, 'two' => 0.0, 'three' => 0.0],
                ['one' => 98, 'two' => 1, 'three' => 1],
            ],
            'several-columns-matching-100' => [
                ['one' => 20.0, 'two' => 30.0, 'three' => 50.0],
                ['one' => 20, 'two' => 30, 'three' => 50],
            ],
            'several-columns-below-100' => [
                // leftover percentages are filled from the last upwards
                ['one' => 19.0, 'two' => 29.0, 'three' => 50.0],
                ['one' => 19, 'two' => 30, 'three' => 51],
            ],
            'above-100-all-columns-filled' => [
                ['one' => 1400.0, 'two' => 900.0, 'three' => 700.0],
                ['one' => 38, 'two' => 33, 'three' => 29],
            ],
            'above-100-several-empty-columns' => [
                ['one' => 1400.0, 'two' => 900.0, 'three' => 0.0, 'four' => 0.0],
                ['one' => 30, 'two' => 19, 'three' => 25, 'four' => 26],
            ],
            'above-100-single-filled-column' => [
                ['one' => 1400.0, 'two' => 0.0, 'three' => 0.0],
                ['one' => 98, 'two' => 1, 'three' => 1],
            ],
            'single-column-array-below-100' => [
                ['one' => 80.0],
                ['one' => 100],
            ],
            'single-column-array-above-100' => [
                ['one' => 8045.0],
                ['one' => 100],
            ],
            'two-col-exact' => [
                ['A' => 50.0, 'B' => 50.0],
                ['A' => 50, 'B' => 50],
            ],
            'two-col-missing' => [
                ['A' => 80.0, 'B' => 0.0],
                ['A' => 80, 'B' => 20],
            ],
            'two-col-overflow' => [
                ['A' => 200.0, 'B' => 100.0],
                ['A' => 59, 'B' => 41],
            ],
            'all-equal-four-cols' => [
                ['A' => 25.0, 'B' => 25.0, 'C' => 25.0, 'D' => 25.0],
                ['A' => 25, 'B' => 25, 'C' => 25, 'D' => 25],
            ],
        ];
    }

    /**
     * @param array<string, float> $input
     * @param array<string, int> $expected
     */
    #[DataProvider('provideGetValuesCases')]
    public function test_getValues(array $input, array $expected): void
    {
        $this->assertCalculation($input, $expected);
    }

    /**
     * H1 — setMaxTotal() basic: input already proportional, output must sum to 200.
     */
    public function test_setMaxTotal(): void
    {
        $this->assertCalculation(
            ['A' => 100, 'B' => 50, 'C' => 50],
            ['A' => 100, 'B' => 50, 'C' => 50],
            200
        );
    }

    /**
     * H1 — setMaxTotal() with overflow + missing: overflow column is clamped,
     * missing column is filled, and output must sum to 200.
     */
    public function test_setMaxTotal_withOverflowAndMissing(): void
    {
        $this->assertCalculation(
            ['A' => 900, 'B' => 0, 'C' => 100],
            ['A' => 90, 'B' => 100, 'C' => 10],
            200
        );
    }
}

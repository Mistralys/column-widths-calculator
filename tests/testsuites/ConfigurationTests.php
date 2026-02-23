<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Mistralys\WidthsCalculator\Calculator;

class ConfigurationTests extends CalculatorTestCase
{
    /**
     * M8 — All configuration methods must return the same Calculator instance
     * (fluent interface).
     */
    public function test_fluentInterface(): void
    {
        $calc = Calculator::create(['A' => 50, 'B' => 50]);

        $this->assertSame($calc, $calc->setFloatValues());
        $this->assertSame($calc, $calc->setMaxTotal(200));
        $this->assertSame($calc, $calc->setMinWidth(1));
    }

    /**
     * L1 — getDefaultOptions() must return the correct option keys and default values.
     *
     * Note: getDefaultOptions() is an instance method (not static); the WP-005 spec
     * listed a static call, which contradicts both the source and api-surface.md.
     * Asserting via instance call as mandated by the Failure Protocol.
     */
    public function test_getDefaultOptions(): void
    {
        $expected = [
            'maxTotal'      => 100,
            'minPerCol'     => 1.0,
            'integerValues' => true,
        ];

        $calc = Calculator::create(['A' => 50, 'B' => 50]);
        $this->assertSame($expected, $calc->getDefaultOptions());
    }

    /**
     * L2 — isIntegerMode() must return true by default and false after setFloatValues().
     */
    public function test_isIntegerModeToggle(): void
    {
        $calc = Calculator::create(['A' => 50, 'B' => 50]);

        // Default: integer mode ON
        $this->assertTrue($calc->isIntegerMode());

        // After setFloatValues(): integer mode OFF
        $calc->setFloatValues();
        $this->assertFalse($calc->isIntegerMode());
    }
}

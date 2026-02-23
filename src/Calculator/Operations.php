<?php

/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\Operations} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\Operations
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Central source for shared calculation routines used
 * by all subclasses.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Operations
{
    private int $amountCols;
    private int $missing = 0;

    /**
     * @var Column[]
     */
    private array $columns;

    public function __construct(Calculator $calculator)
    {
        $this->columns = $calculator->getColumns();
        $this->amountCols = count($this->columns);

        foreach ($this->columns as $col) {
            if ($col->isMissing()) {
                $this->missing++;
            }
        }
    }

    public function calcTotal(): float
    {
        $total = 0;

        foreach ($this->columns as $col) {
            $total += $col->getValue();
        }

        return $total;
    }

    public function countColumns(): int
    {
        return $this->amountCols;
    }

    public function countMissing(): int
    {
        return $this->missing;
    }

    public function calcTotalNotMissing(): float
    {
        $total = 0;

        foreach ($this->columns as $col) {
            if (!$col->isMissing()) {
                $total += $col->getValue();
            }
        }

        return $total;
    }
}

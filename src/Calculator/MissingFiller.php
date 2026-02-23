<?php

/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\MissingFiller} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\MissingFiller
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Handles filling the missing column values with meaningful values.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class MissingFiller
{
    private Calculator $calculator;
    private Operations $operations;
    private int $missing;

    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
        $this->operations = $calculator->getOperations();
        $this->missing = $this->operations->countMissing();
    }

    public function fill(): void
    {
        if ($this->missing === 0) {
            return;
        }

        $perColumn = $this->calcPerColumn();

        $this->applyToColumns($perColumn);
    }

    private function applyToColumns(float $perColumn): void
    {
        $cols = $this->calculator->getColumns();

        foreach ($cols as $col) {
            if ($col->isMissing()) {
                $col->setValue($perColumn);
            }
        }
    }

    private function calcPerColumn(): float
    {
        $toDistribute = $this->calculator->getMaxTotal() - $this->operations->calcTotal();

        if ($toDistribute <= 0) {
            $toDistribute = $this->calculator->getMinWidth() * $this->missing;
        }

        $perColumn = $toDistribute / $this->missing;

        if (!$this->calculator->isIntegerMode()) {
            $perColumn = round($perColumn, 10);
        }

        return $perColumn;
    }
}

<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\SurplusRemover} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\SurplusRemover
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Handles subtracting values above the maximum total.
 * This can happen when there are missing values combined
 * with a minimum column width.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class SurplusRemover
{
    private Calculator $calculator;
    private Operations $operations;
    private float $leftover = 0;
    private float $baseTotal = 0;
    
    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
        $this->operations = $calculator->getOperations();
    }
    
    public function remove() : void
    {
        $this->leftover = $this->calculator->getMaxTotal() - $this->operations->calcTotal();
        
        if($this->leftover >= 0)
        {
            return;
        }
        
        $this->leftover *= -1; // we want a positive number
        $this->baseTotal = $this->operations->calcTotalNotMissing();
        $cols = $this->calculator->getColumns();
        
        foreach($cols as $col)
        {
            if(!$this->processColumn($col))
            {
                break;
            }
        }
        
        // There is some surplus left after the operation:
        // this means there were columns from which the 
        // surplus could not be removed because of the min
        // column width. 
        //
        // We simply run the removal again, to remove the 
        // surplus from the columns it can be removed from.
        if($this->leftover > 0)
        {
            $this->remove();
        }
    }
    
    private function processColumn(Column $col) : bool
    {
        if($col->isMissing())
        {
            return true;
        }
        
        if($this->leftover <= 0)
        {
            return false;
        }
        
        $percent = $col->getValue() * 100 / $this->baseTotal;
        $amount = round($this->leftover * $percent / 100);
        $val = $col->getValue() - $amount;
        
        if($val < $this->calculator->getMinWidth())
        {
            return true;
        }
        
        $this->leftover -= $amount;
        
        $col->setValue($val);
        
        return true;
    }
}

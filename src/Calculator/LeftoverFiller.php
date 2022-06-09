<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\LeftoverFiller} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\LeftoverFiller
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Handles filling any percentages missing to reach the full 100%.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class LeftoverFiller
{
    private Calculator $calculator;
    private Operations $operations;
    
   /**
    * @var Column[]
    */
    private array $columns;
    
    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
        $this->operations = $calculator->getOperations();
        $this->columns = $calculator->getColumns();
    }
    
    public function fill() : void
    {
        $leftover = $this->calculator->getMaxTotal() - $this->operations->calcTotal();
        $perCol = $leftover / $this->operations->countColumns();

        if($this->calculator->isIntegerMode())
        {
            $perCol = (int)ceil($perCol);
        }

        for($i=($this->operations->countColumns()-1); $i >=0; $i--)
        {
            if($leftover <= 0)
            {
                break;
            }
            
            $leftover -= $perCol;
            
            $col = $this->columns[$i];
            
            $val = $col->getValue() + $perCol;
            
            $col->setValue($val);
        }

        $this->cleanUp($leftover);
    }
    
   /**
    * In integer mode, after filling all items, because of rounding
    * the amount of column up, we may have added a bit too much. We
    * fix this here, by removing it from the last column.
    * 
    * @param float $leftover
    */
    private function cleanUp(float $leftover) : void
    {
        if($leftover >= 0 || empty($this->columns))
        {
            return;
        }

        $col = array_pop($this->columns);
        
        $col->setValue($col->getValue() + $leftover);
    }
}

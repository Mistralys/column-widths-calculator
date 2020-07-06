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
    /**
     * @var Calculator
     */
    private $calculator;
    
    /**
     * @var Operations
     */
    private $operations;
    
   /**
    * @var Column[]
    */
    private $columns = array();
    
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
    }
}

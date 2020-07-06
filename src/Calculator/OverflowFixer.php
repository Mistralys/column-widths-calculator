<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\OverflowFixer} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\OverflowFixer
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Handles converting values that are out of bounds to
 * values that the calculator can work with.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class  OverflowFixer
{
   /**
    * @var Calculator
    */
    private $calculator;
    
   /**
    * @var Operations
    */
    private $operations;
    
    public function __construct(Calculator $calculator)
    {
        $this->calculator = $calculator;
        $this->operations = $calculator->getOperations();
    }
    
    public function fix() : void
    {
        $total = $this->operations->calcTotal();
        
        // to allow space for the missing columns, we base the
        // total target percentage on the amount of columns that
        // are not missing.
        $maxTotal = $this->calculator->getMaxTotal() / ($this->operations->countColumns() - $this->operations->countMissing());
        
        $cols = $this->calculator->getColumns();
        
        foreach($cols as $col)
        {
            // no change for missing columns, they get filled later
            if($col->isMissing())
            {
                continue;
            }
            
            $percentage = $col->getValue() * 100 / $total;
            $adjusted = floor($maxTotal * $percentage / 100);
            
            $col->setValue((int)$adjusted);
        }
    }
}

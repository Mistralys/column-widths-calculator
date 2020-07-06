<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator;

use Mistralys\WidthsCalculator\Calculator\Column;

/**
 * Calculates percentual column widths given a list of 
 * column names with user width values.
 *
 * Columns with 0 width are filled automatically with
 * the leftover percent. Values out of bounds are 
 * normalized proportionally, allowing the use of an
 * arbitrary numering system to convert to percent.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Calculator
{
   /**
    * @var integer
    */
    private $amountCols = 0;

   /**
    * @var integer
    */
    private $maxTotal = 100;
    
   /**
    * @var integer
    */
    private $minPerCol = 1;
    
   /**
    * @var Column[]
    */
    private $columns = array();
    
   /**
    * @var integer
    */
    private $missing = 0;
    
   /**
    * @var boolean
    */
    private $calculated = false;
    
   /**
    * @param string[]number $columnValues
    */
    private function __construct(array $columnValues)
    {
        foreach($columnValues as $name => $value)
        {
            $this->addColumn(
                (string)$name, 
                intval($value)
            );
        }
    }
    
    public static function create(array $columnValues) : Calculator
    {
        return new Calculator($columnValues);
    }
    
    private function addColumn(string $name, int $value) : void
    {
        $col = new Column(
            $this,
            $name,
            $value
        );
        
        if($col->isMissing())
        {
            $this->missing++;
        }
        
        $this->amountCols++;
        
        $this->columns[] = $col;
    }
    
   /**
    * Retrieves the minimum width for columns, in percent.
    * @return float
    */
    public function getMinWidth() : float
    {
        return $this->minPerCol;
    }
    
    private function calculate() : void
    {
        if($this->calculated)
        {
            return;
        }
        
        if($this->calcTotal() > $this->maxTotal)
        {
            $this->fixOverflow();
        }
        
        $this->fillMissing();
        $this->fillLeftover();
        
        $this->calculated = true;
    }
    
   /**
    * Retrieves the updated list of column values, 
    * retaining the original keys.
    * 
    * @return array
    */
    public function getValues() : array
    {
        $this->calculate();
        
        $result = array();
        
        foreach($this->columns as $col)
        {
            $result[$col->getName()] = $col->getValue();
        }
        
        return $result;
    }
    
    private function calcTotal() : int
    {
        $total = 0;
        
        foreach($this->columns as $col)
        {
            $total += $col->getValue();
        }
        
        return $total;
    }
    
    private function fillLeftover() : void
    {
        $leftover = $this->maxTotal - $this->calcTotal();
        $perCol = (int)ceil($leftover / $this->amountCols);
        
        for($i=($this->amountCols-1); $i >=0; $i--)
        {
            if($leftover <= 0)
            {
                break;
            }
            
            $leftover -= $perCol;
            
            $col = $this->columns[$i];
            
            $col->setValue($col->getValue() + $perCol);
        }
    }
    
    private function fillMissing() : void
    {
        if($this->missing === 0)
        {
            return;
        }
        
        $toDistribute = $this->maxTotal - $this->calcTotal();
        $perMissingCol = (int)floor($toDistribute / $this->missing);
        
        foreach($this->columns as $col)
        {
            if($col->isMissing())
            {
                $col->setValue($perMissingCol);
            }
        }
    }
    
    private function fixOverflow() : void
    {
        // special case: if only a single column value has
        // been specified, and this exceeds 100%, it is 
        // impossible to determine what value the other 
        // columns should have. In this case, we reset all
        // values.
        if($this->missing === ($this->amountCols - 1))
        {
            foreach($this->columns as $col)
            {
                $col->setValue(0);
                $col->makeMissing();
            }
            
            $this->missing = $this->amountCols;
            
            return;
        }
        
        $total = $this->calcTotal();

        // to allow space for the missing columns, we base the 
        // total target percentage on the amount of columns that
        // are not missing.
        $maxTotal = $this->maxTotal / ($this->amountCols - $this->missing);
        
        foreach($this->columns as $col)
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

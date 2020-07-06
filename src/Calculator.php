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
use AppUtils\Traits_Optionable;
use AppUtils\Interface_Optionable;

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
class Calculator implements Interface_Optionable
{
    const ERROR_INVALID_MIN_WIDTH = 61501;
    
    use Traits_Optionable;
    
   /**
    * @var integer
    */
    private $amountCols = 0;

   /**
    * @var integer
    */
    private $maxTotal = 100;
    
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
                floatval($value)
            );
        }
    }
    
    public static function create(array $columnValues) : Calculator
    {
        return new Calculator($columnValues);
    }
    
    public function getDefaultOptions(): array
    {
        return array(
            'minPerCol' => 1,
            'integerValues' => true
        );
    }
    
    public function setFloatValues(bool $enable=true) : Calculator
    {
        $this->setOption('integerValues', !$enable);
        return $this;
    }
    
   /**
    * Sets the minimum width to enforce for columns, 
    * when there already are other columns that take
    * up all the available width.
    * 
    * @param float $width
    * @return Calculator
    */
    public function setMinWidth(float $width) : Calculator
    {
        $max = $this->getMaxMinWidth();
        
        if($width > $max)
        {
            throw new \Exception(
                sprintf('Minimum width cannot be set above %s.', number_format($max, 4)),
                self::ERROR_INVALID_MIN_WIDTH
            );
        }
        
        $this->setOption('minPerCol', $width);
        return $this;
    }
    
    public function getMaxMinWidth() : float
    {
        return $this->maxTotal / $this->amountCols;
    }
    
    private function addColumn(string $name, float $value) : void
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
        return floatval($this->getOption('minPerCol'));
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
        $this->removeSurplus();
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
            $val = $col->getValue();
            
            if($this->getBoolOption('integerValues'))
            {
                $val = intval($val);
            }
            
            $result[$col->getName()] = $val;
        }
        
        return $result;
    }
    
    private function calcTotal() : float
    {
        $total = 0;
        
        foreach($this->columns as $col)
        {
            $total += $col->getValue();
        }
        
        return $total;
    }
    
    private function removeSurplus() : void
    {
        $leftover = $this->maxTotal - $this->calcTotal();
        
        if($leftover >= 0)
        {
            return;
        }

        $leftover = $leftover * -1;
        $baseTotal = $this->calcTotalNotMissing();
        $min = $this->getMinWidth();
        
        foreach($this->columns as $col)
        {
            if($col->isMissing())
            {
                continue;
            }
            
            if($leftover <= 0)
            {
                break;
            }
            
            $percent = $col->getValue() * 100 / $baseTotal;
            $amount = round($leftover * $percent / 100);
            $val = $col->getValue() - $amount;
            
            if($val < $min)
            {
                continue;
            }
            
            $leftover -= $amount;
            
            $col->setValue($val);
        }
        
        if($leftover > 0)
        {
            $this->removeSurplus();
        }
    }
    
    private function calcTotalNotMissing() : float
    {
        $total = 0;
        
        foreach($this->columns as $col)
        {
            if(!$col->isMissing())
            {
                $total += $col->getValue();
            }
        }
        
        return $total;
    }
    
   /**
    * Detects any leftover percentages that still need
    * to be filled, in case we are not at 100% yet. It
    * distributes the missing percentages evenly over the
    * available columns, from the last one upwards.
    */
    private function fillLeftover() : void
    {
        $this->adjustLeftoverValues();
        
        $leftover = $this->maxTotal - $this->calcTotal();
        $perCol = $leftover / $this->amountCols;

        if($this->getBoolOption('integerValues'))
        {
            $perCol = (int)ceil($perCol);
        }
        
        for($i=($this->amountCols-1); $i >=0; $i--)
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
    
   /**
    * Adjusts the individual column values to match
    * the expected output format, for example ensuring
    * integer values if we are in integer mode.
    */
    private function adjustLeftoverValues() : void
    {
        // convert all columns to integer values as required
        if($this->getBoolOption('integerValues'))
        {
            foreach($this->columns as $col)
            {
                $val = intval(floor($col->getValue()));
                $col->setValue(floatval($val));
            }
        }
    }
    
    private function fillMissing() : void
    {
        if($this->missing === 0)
        {
            return;
        }
        
        $toDistribute = $this->maxTotal - $this->calcTotal();
        
        if($toDistribute <= 0)
        {
            $toDistribute = $this->getMinWidth() * $this->missing;
        }
        
        $perMissingCol = $toDistribute / $this->missing;
        
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

<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator;

use AppUtils\Traits_Optionable;
use AppUtils\Interface_Optionable;
use Mistralys\WidthsCalculator\Calculator\Column;
use Mistralys\WidthsCalculator\Calculator\Operations;
use Mistralys\WidthsCalculator\Calculator\OverflowFixer;
use Mistralys\WidthsCalculator\Calculator\SurplusRemover;
use Mistralys\WidthsCalculator\Calculator\MissingFiller;
use Mistralys\WidthsCalculator\Calculator\LeftoverFiller;

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
    * @var Column[]
    */
    private $columns = array();
    
   /**
    * @var boolean
    */
    private $calculated = false;
    
   /**
    * @var Operations
    */
    private $operations;
    
   /**
    * @param array<string,float> $columnValues
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
        
        $this->operations = new Operations($this);
    }
    
   /**
    * Creates an instance of the calculator.
    * 
    * @param array<string,float> $columnValues
    * @return Calculator
    */
    public static function create(array $columnValues) : Calculator
    {
        return new Calculator($columnValues);
    }
    
   /**
    * @return array<string,mixed>
    */
    public function getDefaultOptions(): array
    {
        return array(
            'maxTotal' => 100,
            'minPerCol' => 1,
            'integerValues' => true
        );
    }
    
    public function getMaxTotal() : float
    {
        return floatval($this->getOption('maxTotal'));
    }
    
    public function getOperations() : Operations
    {
        return $this->operations;
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
        return $this->getMaxTotal() / $this->operations->countColumns();
    }
    
    private function addColumn(string $name, float $value) : void
    {
        $col = new Column(
            $this,
            $name,
            $value
        );
        
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
        
        if($this->operations->calcTotal() > $this->getMaxTotal())
        {
            $this->fixOverflow();
        }
        
        $this->fillMissing();
        $this->removeSurplus();
        $this->convertToInteger();
        $this->fillLeftover();
        
        $this->calculated = true;
    }
    
   /**
    * Adjusts the individual column values to match
    * the expected output format, for example ensuring
    * integer values if we are in integer mode.
    */
    private function convertToInteger() : void
    {
        // convert all columns to integer values as required
        if($this->isIntegerMode())
        {
            foreach($this->columns as $col)
            {
                $val = intval(floor($col->getValue()));
                $col->setValue(floatval($val));
            }
        }
    }
    
   /**
    * Retrieves the updated list of column values, 
    * retaining the original keys.
    * 
    * @return array<string,int|float>
    */
    public function getValues() : array
    {
        $this->calculate();
        
        $result = array();
        
        foreach($this->columns as $col)
        {
            $val = $col->getValue();
            
            if($this->isIntegerMode())
            {
                $val = intval($val);
            }
            
            $result[$col->getName()] = $val;
        }
        
        return $result;
    }
    
    public function isIntegerMode() : bool
    {
        return $this->getBoolOption('integerValues');
    }
    
   /**
    * @return Column[]
    */
    public function getColumns() : array 
    {
        return $this->columns;
    }
    
    private function removeSurplus() : void
    {
        $surplus = new SurplusRemover($this);
        $surplus->remove();
    }
    
   /**
    * Detects any leftover percentages that still need
    * to be filled, in case we are not at 100% yet. It
    * distributes the missing percentages evenly over the
    * available columns, from the last one upwards.
    */
    private function fillLeftover() : void
    {
        $filler = new LeftoverFiller($this);
        $filler->fill();
    }
    
    private function fillMissing() : void
    {
        $filling = new MissingFiller($this);
        $filling->fill();
    }
    
    private function fixOverflow() : void
    {
        $overflow = new OverflowFixer($this);
        $overflow->fix();
    }
}

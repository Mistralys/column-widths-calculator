<?php
/**
 * File containing the {@see Mistralys\WidthsCalculator\Calculator\Column} class.
 *
 * @package WidthsCalculator
 * @see Mistralys\WidthsCalculator\Calculator\Column
 */

declare (strict_types=1);

namespace Mistralys\WidthsCalculator\Calculator;

use Mistralys\WidthsCalculator\Calculator;

/**
 * Container class for a single column in the values list.
 *
 * @package WidthsCalculator
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class Column
{
   /**
    * @var Calculator
    */
    private $calculator;
    
   /**
    * @var string
    */
    private $name;
    
   /**
    * @var float
    */
    private $value;
    
   /**
    * @var boolean
    */
    private $missing = false;
    
    public function __construct(Calculator $calculator, string $name, float $value)
    {
        $this->calculator = $calculator;
        $this->name = $name;
        $this->value = $value;
        $this->missing = $value <= 0;
    }
    
    public function getName() : string
    {
        return $this->name;
    }
    
    public function isMissing() : bool
    {
        return $this->missing;
    }
    
    public function getValue() : float
    {
        return $this->value;
    }
    
    public function setValue(float $value) : void
    {
        $this->value = $value;
    }
    
    public function makeMissing() : void
    {
        $this->missing = true;
    }
}

<?php

declare(strict_types=1);

namespace Mistralys\WidthsCalculatorUnitTests;

use Exception;
use Mistralys\WidthsCalculator\Calculator;

class MinWidthTests extends CalculatorTestCase
{
    public function test_setMinWidth() : void
    {
        $values = array(
            'col1' => 100,
            'col2' => 0,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        $calc->setMinWidth(20);
        
        $expected = array(
            'col1' => 60,
            'col2' => 20,
            'col3' => 20
        );
        
        $this->assertEquals($expected, $calc->getValues());
    }
    
    public function test_setMinWidth_surplus() : void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        $calc->setMinWidth(20);
        
        // surplus is removed proportionally from 
        // existing columns.
        $expected = array(
            'col1' => 60,
            'col2' => 20,
            'col3' => 20
        );
        
        $this->assertEquals($expected, $calc->getValues());
    }
    
    public function test_surplus() : void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        
        // surplus is removed proportionally from
        // existing columns.
        $expected = array(
            'col1' => 79,
            'col2' => 20,
            'col3' => 1
        );
        
        $this->assertEquals($expected, $calc->getValues());
    }
    
    public function test_surplus_alternate() : void
    {
        $values = array(
            'col1' => 70,
            'col2' => 30,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        $calc->setMinWidth(20);
        
        // surplus is removed proportionally from
        // existing columns.
        $expected = array(
            'col1' => 52,
            'col2' => 28,
            'col3' => 20
        );
        
        $this->assertEquals($expected, $calc->getValues());
    }
    
    public function test_maxMinWidth() : void
    {
        $values = array(
            'col1' => 80,
            'col2' => 20,
            'col3' => 0
        );
        
        $calc = Calculator::create($values);
        
        $this->expectException(Exception::class);
        
        $calc->setMinWidth(35); // max is 33.3333
    }
}

<?php
/**
 * Main bootstrapper used to set up the testsuite environment.
 * 
 * @package WidthsCalculator
 * @subpackage Tests
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */

declare(strict_types=1);

const TESTS_ROOT = __DIR__;

$autoloader = __DIR__.'/../vendor/autoload.php';
    
if(!file_exists($autoloader))
{
    die('ERROR: The autoloader is not present. Run composer install first.');
}

require_once $autoloader;

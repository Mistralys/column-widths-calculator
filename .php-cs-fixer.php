<?php
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->exclude('vendor');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'braces_position' => [
            'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
            'classes_opening_brace'   => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace' => 'same_line',
        ],
        'no_spaces_after_function_name' => true,
    ])
    ->setFinder($finder);

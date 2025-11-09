<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP81Migration' => true,
        '@PHP82Migration' => true,
        'strict_param' => true,
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'phpdoc_order' => true,
        'phpdoc_to_comment' => false,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
        'single_line_throw' => false,
        'types_spaces' => ['space' => 'none'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'binary_operator_spaces' => [
            //'operators' => ['=>' => 'align', '=' => 'align_single_space_minimal']
            'operators' => ['=>' => 'single_space', '='  => 'single_space'],
        ],
        'yoda_style' => true,
        'native_function_invocation' => [
            //'include' => ['@all'],
            'include' => ['@compiler_optimized'],
            'strict' => false,
        ],
    ]);

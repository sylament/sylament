<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return new Config()
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'declare_strict_types' => true,
        'explicit_string_variable' => true,
        'not_operator_with_successor_space' => false,
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_param_order' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'array_push' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'final_class' => false,
        'final_internal_class' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'mb_str_functions' => true,
        'modernize_types_casting' => true,
        'new_with_parentheses' => false,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'no_unused_imports' => true,
        'no_multiple_statements_per_line' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'ordered_interfaces' => true,
        'ordered_traits' => true,
        'self_accessor' => true,
        'self_static_accessor' => true,
        'strict_comparison' => true,
        'visibility_required' => true,
        'increment_style' => [
            'style' => 'post',
        ],
    ])
    ->setFinder(
        new Finder()
            ->in(__DIR__ . '/packages/*/src')
            ->in(__DIR__ . '/packages/*/tests')
    );

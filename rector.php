<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages/*/src',
        __DIR__ . '/packages/*/tests',
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withImportNames()
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        EncapsedStringsToSprintfRector::class,
    ]);

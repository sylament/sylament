<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/packages/*/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php85: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
    )
    ->withImportNames()
    ->withSkip([
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        // The anonymous pages' empty mount() signatures are the route placeholders the test mounts.
        RemoveEmptyClassMethodRector::class => [__DIR__ . '/tests/Packages/Panels/Routing/PanelRouteLoaderTest.php'],
    ]);

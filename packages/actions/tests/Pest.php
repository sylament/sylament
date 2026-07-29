<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests;

use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Support\Icons\IconManager;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
        ComponentResolver::unsetInstance();
    })
    ->in('Unit');

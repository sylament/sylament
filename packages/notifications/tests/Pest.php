<?php

declare(strict_types=1);

namespace Sylament\Notifications\Tests;

use Sylament\Support\Configuration\ComponentManager;
use Sylament\Support\DependencyInjection\ComponentResolver;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
        ComponentResolver::unsetInstance();
    })
    ->in('Unit');

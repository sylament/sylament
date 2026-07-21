<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Sylament\Support\Component\ComponentManager;
use Sylament\Support\Icons\IconManager;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
    })
    ->in('Unit');

<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Sylament\Support\Component\ComponentManager;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->afterEach(function (): void {
        ComponentManager::unsetInstance();
    })
    ->in('Unit');

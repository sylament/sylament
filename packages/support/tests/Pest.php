<?php

declare(strict_types=1);

namespace Sylament\Support\Tests;

use Sylament\Support\Components\ComponentManager;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Evaluation\Invoker;
use Symfony\Component\DependencyInjection\Container;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->beforeEach(function (): void {
        Invoker::setInstance(new Invoker(new Container()));
    })
    ->afterEach(function (): void {
        Invoker::unsetInstance();
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
    })
    ->in('Unit');

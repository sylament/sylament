<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests;

use Sylament\Support\Component\ComponentManager;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Service\Invoker;
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

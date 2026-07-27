<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests;

use Sylament\Schemas\State\PropertyAccessor;
use Sylament\Support\Components\ComponentManager;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Evaluation\Invoker;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\PropertyAccess\PropertyAccess;

pest()
    ->extend(TestCase::class)
    ->in('Integration');

pest()
    ->beforeEach(function (): void {
        Invoker::setInstance(new Invoker(new Container()));
        PropertyAccessor::setInstance(new PropertyAccessor(PropertyAccess::createPropertyAccessor()));
    })
    ->afterEach(function (): void {
        Invoker::unsetInstance();
        PropertyAccessor::unsetInstance();
        ComponentManager::unsetInstance();
        IconManager::unsetInstance();
    })
    ->in('Unit');

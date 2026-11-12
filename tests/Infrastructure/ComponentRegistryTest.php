<?php

declare(strict_types=1);

use Sylament\Forms\Components\Contracts\HasAffixes;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Tests\Infrastructure\ComponentRegistry;

it('reads the list without leaving a static instance bound', function (): void {
    ComponentRegistry::componentsImplementing(HasAffixes::class);

    expect(fn (): ComponentResolver => ComponentResolver::getInstance())->toThrow(RuntimeException::class);
});

it('lists every registered component that implements a contract, by short name', function (): void {
    expect(ComponentRegistry::componentsImplementing(HasAffixes::class))
        ->toHaveKeys(['TextInput', 'DatePicker'])
        ->each->not->toStartWith('Sylament\\Tests\\');
});

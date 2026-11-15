<?php

declare(strict_types=1);

use Sylament\Forms\Components\Concerns\BelongsToRelationship;
use Sylament\Forms\Components\Contracts\HasAffixes;
use Sylament\Support\DependencyInjection\ComponentResolver;
use Sylament\Tests\Infrastructure\ComponentRegistry;

it('reads the list without leaving a static instance bound', function (): void {
    // A memo an earlier case warmed would leave this case reading nothing.
    new ReflectionProperty(ComponentRegistry::class, 'components')->setValue(null, null);

    ComponentRegistry::componentsImplementing(HasAffixes::class);

    expect(fn (): ComponentResolver => ComponentResolver::getInstance())->toThrow(RuntimeException::class);
});

it('lists every registered component that implements a contract, by short name', function (): void {
    expect(ComponentRegistry::componentsImplementing(HasAffixes::class))
        ->toHaveKeys(['TextInput', 'DatePicker'])
        ->each->not->toStartWith('Sylament\\Tests\\');
});

it('lists a component that uses a trait through a trait it composes, and one that does not use it', function (): void {
    expect(ComponentRegistry::componentsUsing(BelongsToRelationship::class))
        ->toHaveKey('Select')
        ->not->toHaveKey('TextInput');
});

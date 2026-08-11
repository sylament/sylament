<?php

declare(strict_types=1);

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;

it('reports no relationship until one is declared', function (): void {
    expect(Repeater::make('sections')->hasRelationship())->toBeFalse()
        ->and(Repeater::make('sections')->getRelationshipName())->toBeNull()
        ->and(Repeater::make('sections')->relationship()->getRelationshipName())->toBe('sections')
        ->and(Repeater::make('sections')->relationship('parts')->hasRelationship())->toBeTrue()
        ->and(Repeater::make('sections')->relationship('parts')->getRelationshipName())->toBe('parts');
});

it('closes each refusal arm with the advice of the field that raised it', function (): void {
    $repeater = Repeater::make('sections')
        ->record(Post::class)
        ->relationship()
        ->components([TextInput::make('heading')]);

    expect(fn (): mixed => $repeater->loadStateFromRelationships(new Post()))
        ->toThrow(LogicException::class, 'or drop relationship() and map the items onto the record by hand after getData().')
        ->and(fn (): array => Select::make('author')->record(Post::class)->relationship(titleAttribute: 'name')->getOptions())
        ->toThrow(LogicException::class, 'or replace relationship() with an options() map.');
});

it('refuses a query scope the collection read could never run', function (): void {
    expect(fn (): Repeater => Repeater::make('sections')->relationship(modifyQueryUsing: static fn (): null => null))
        ->toThrow(LogicException::class, 'modifyQueryUsing');
});

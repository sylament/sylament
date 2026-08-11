<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;

it('reports no relationship until one is declared', function (): void {
    expect(Select::make('author')->hasRelationship())->toBeFalse()
        ->and(Select::make('author')->getRelationshipName())->toBeNull()
        ->and(Select::make('author')->relationship()->getRelationshipName())->toBe('author')
        ->and(Select::make('author')->relationship()->hasRelationship())->toBeTrue();
});

it('carries the declared title attribute', function (): void {
    expect(Select::make('author')->relationship(titleAttribute: 'name')->getRelationshipTitleAttribute())->toBe('name')
        ->and(Select::make('author')->relationship()->getRelationshipTitleAttribute())->toBeNull();
});

it('throws loudly when no entity manager backs a relationship select', function (): void {
    $select = Select::make('author')->record(Post::class)->relationship(titleAttribute: 'name');

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'no Doctrine EntityManager');
});

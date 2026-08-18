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

it('installs the Doctrine option defaults unconditionally, so declaration order decides', function (): void {
    $before = static fn (): string => 'before';
    $after = static fn (): string => 'after';

    $overwritten = Select::make('author')
        ->createOptionUsing($before)
        ->updateOptionUsing($before)
        ->relationship(titleAttribute: 'name');

    $wins = Select::make('author')
        ->relationship(titleAttribute: 'name')
        ->createOptionUsing($after)
        ->updateOptionUsing($after);

    expect($overwritten->getCreateOptionUsing())->not->toBe($before)
        ->and($overwritten->getCreateOptionUsing())->toBeInstanceOf(Closure::class)
        ->and($overwritten->getUpdateOptionUsing())->not->toBe($before)
        ->and($wins->getCreateOptionUsing())->toBe($after)
        ->and($wins->getUpdateOptionUsing())->toBe($after);
});

it('throws loudly when no entity manager backs a relationship select', function (): void {
    $select = Select::make('author')->record(Post::class)->relationship(titleAttribute: 'name');

    expect(fn (): array => $select->getOptions())
        ->toThrow(LogicException::class, 'no Doctrine EntityManager');
});

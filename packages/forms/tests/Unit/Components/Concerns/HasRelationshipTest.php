<?php

declare(strict_types=1);

use Sylament\Forms\Components\Select;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Forms\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Schema;

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

it('installs the search and label arms unconditionally too, so declaration order decides there as well', function (): void {
    $mine = static fn (): array => ['x' => 'X'];

    $overwritten = Select::make('tags')
        ->multiple()
        ->getSearchResultsUsing($mine)
        ->getOptionLabelsUsing($mine)
        ->relationship(titleAttribute: 'name');

    $wins = Select::make('tags')
        ->multiple()
        ->relationship(titleAttribute: 'name')
        ->getSearchResultsUsing($mine)
        ->getOptionLabelsUsing($mine);

    foreach ([$overwritten, $wins] as $select) {
        Schema::make(new FieldRenderHost())->statePath('data')->record(Post::class)->components([$select])->getComponents();
        $select->state(['1']);
    }

    expect(fn (): array => $overwritten->getSearchResults('x'))
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and(fn (): array => $overwritten->getOptionLabels())
        ->toThrow(LogicException::class, 'no Doctrine EntityManager')
        ->and($wins->getSearchResults('x'))->toBe(['x' => 'X'])
        ->and($wins->getOptionLabels())->toBe(['x' => 'X']);
});

it('defaults the search columns to the title attribute, and lets searchable(array) name them', function (): void {
    expect(Select::make('author')->getSearchColumns())->toBeNull()
        ->and(Select::make('author')->relationship()->getSearchColumns())->toBeNull()
        ->and(Select::make('author')->relationship(titleAttribute: 'name')->getSearchColumns())->toBe(['name'])
        ->and(Select::make('author')->relationship(titleAttribute: 'name')->searchable(['name', 'email'])->getSearchColumns())->toBe(['name', 'email'])
        ->and(Select::make('author')->searchable(['name'])->getSearchColumns())->toBe(['name'])
        ->and(Select::make('author')->searchable(['name'])->searchable()->getSearchColumns())->toBeNull();
});

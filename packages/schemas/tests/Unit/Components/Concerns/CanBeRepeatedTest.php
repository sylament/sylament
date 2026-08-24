<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components\Concerns;

use Sylament\Schemas\Components\Text;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\KeyedItemsContainer;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('resolves the owning keyed-item container, the item schema and the item index from inside an item', function (): void {
    $container = KeyedItemsContainer::make('rows')
        ->itemKeys(['k1', 'k2'])
        ->components([Text::make('inner')]);

    Schema::make(new HasSchemasFixture())->statePath('data')->components([$container])->getComponents();

    $secondItem = $container->getChildSchema('k2');

    expect($secondItem)->toBeInstanceOf(Schema::class);

    $inner = $secondItem->getComponents()[0];

    expect($inner->getParentRepeaterItem())->toBe($secondItem)
        ->and($inner->getParentRepeater())->toBe($container)
        ->and($inner->getParentRepeaterItemIndex())->toBe(1);
});

it('answers null and zero outside any keyed-item container', function (): void {
    $text = Text::make('plain');
    Schema::make(new HasSchemasFixture())->components([$text])->getComponents();

    expect($text->getParentRepeaterItem())->toBeNull()
        ->and($text->getParentRepeater())->toBeNull()
        ->and($text->getParentRepeaterItemIndex())->toBe(0);
});

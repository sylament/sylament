<?php

declare(strict_types=1);

use Sylament\Forms\Components\TextInput;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;

it('renders the components of its schema in place of its field, which no field modifier reaches', function (): void {
    $crawler = $this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler();

    expect($crawler)
        ->toContainElement(Selectors::filterField('stocked', 'state'))
        ->toContainElement(Selectors::filterField('stocked', 'note'))
        ->and($crawler->filter(Selectors::filterField('stocked', 'isActive'))->count())->toBe(0)
        ->and($crawler->filter(Selectors::filterGroup('stocked'))->text())->not->toContain('Never shown');
});

it('hands its field to a modifier, which may relabel it', function (): void {
    expect($this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler())
        ->toContainElement('label[for="tableFiltersForm.owned.isActive"]', 'Mine only');
});

it('renders no field when it has neither a schema nor a field of its own', function (): void {
    $crawler = $this->createLiveComponent(ShapedJarFilterTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::filterGroup('bare'))
        ->and($crawler->filter(Selectors::filterGroup('bare') . ' ' . FormsSelectors::FIELD_WRAPPER)->count())->toBe(0)
        ->and($crawler->filter(Selectors::filterGroup('sealed') . ' ' . FormsSelectors::FIELD_WRAPPER)->count())->toBe(1);
});

it('answers the live schema its group holds, and refuses a filter the form holds no group for', function (): void {
    $table = $this->createLiveComponent(ShapedJarFilterTable::class)->component()->getTable();

    expect($table->getFilter('stocked')->getSchema()->getComponent('note'))->toBeInstanceOf(TextInput::class)
        ->and(fn () => $table->getFilter('hiddenOutright', withHidden: true)->getSchema())
        ->toThrow(LogicException::class, 'Filter [hiddenOutright] has no group in the filters form.');
});

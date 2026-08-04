<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration\Components;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Forms\Tests\Fixtures\Concerns\FieldRenderHost;
use Sylament\Schemas\Schema;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

use function Sylament\Forms\Tests\renderToHtml;

uses(InteractsWithLiveComponents::class);

/*
 * The two repeater partials through the real Twig stack. Items are seeded on the
 * host bag rather than hydrated, so they stay positionally keyed and the ids the
 * template composes are readable in an assertion; a real mount keys them by uuid,
 * which is why nothing here asserts a literal key.
 */

/**
 * Render a repeater as the child of a real Schema over a seeded `data` bag.
 *
 * @param array<string, mixed> $data
 */
function renderRepeater(Repeater $repeater, array $data = []): Crawler
{
    $host = new FieldRenderHost();
    $host->data = $data;

    return new Crawler(renderToHtml(Schema::make($host)->statePath('data')->components([$repeater])));
}

it('renders one item block per item, each carrying a stable id', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);

    $crawler = renderRepeater($repeater, ['tags' => [['name' => 'a'], ['name' => 'b']]]);

    $items = $crawler->filter('.sy-fo-repeater-item');

    expect($items->count())->toBe(2)
        ->and($items->extract(['id']))->toBe([
            $repeater->getKey() . '.0',
            $repeater->getKey() . '.1',
        ])
        ->and($crawler->filter('.sy-fo-repeater-item-content input')->extract(['id', 'data-model']))->toBe([
            [$repeater->getKey() . '.0.name', 'norender|data.tags.0.name'],
            [$repeater->getKey() . '.1.name', 'norender|data.tags.1.name'],
        ]);
});

it('labels the repeater group with a heading instead of a label element', function (): void {
    $repeater = Repeater::make('tags')->components([TextInput::make('name')]);

    $crawler = renderRepeater($repeater, ['tags' => [['name' => 'a']]]);

    $group = $crawler->filter('.sy-fo-repeater');
    $labelId = $repeater->getId() . '-label';

    expect($group->attr('role'))->toBe('group')
        ->and($group->attr('aria-labelledby'))->toBe($labelId)
        ->and($crawler->filter('div#' . $labelId . '.sy-fo-field-label')->count())->toBe(1)
        ->and($crawler->filter('label[for="' . $repeater->getId() . '"]')->count())->toBe(0);
});

it('renders the delete trigger and no move triggers by default', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($crawler->filter('.sy-fo-repeater-item-header [aria-label="Delete"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move up"]')->count())->toBe(0)
        ->and($crawler->filter('[aria-label="Move down"]')->count())->toBe(0)
        ->and($crawler->filter('[aria-label="Clone"]')->count())->toBe(0);
});

it('renders the move and clone triggers once their surfaces are on', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->reorderableWithButtons()->cloneable(),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($crawler->filter('[aria-label="Move up"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move down"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Clone"]')->count())->toBe(2)
        ->and($crawler->filter('[aria-label="Move up"]')->first()->attr('disabled'))->not->toBeNull()
        ->and($crawler->filter('[aria-label="Move down"]')->last()->attr('disabled'))->not->toBeNull();
});

it('renders no item header when every header surface is off', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->deletable(false),
        ['tags' => [['name' => 'a']]],
    );

    expect($crawler->filter('.sy-fo-repeater-item-header')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item-has-header')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item')->count())->toBe(1);
});

it('renders the item label, numbered when item numbers are on', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->itemLabel('Row')->itemNumbers(),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    $labels = $crawler->filter('.sy-fo-repeater-item-header-label');

    expect($labels->count())->toBe(2)
        ->and($labels->first()->text())->toBe('Row 1')
        ->and($labels->last()->text())->toBe('Row 2')
        ->and($labels->first()->attr('class'))->toContain('sy-truncated');
});

it('renders the add trigger and drops it once maxItems is reached', function (): void {
    $addable = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->maxItems(2),
        ['tags' => [['name' => 'a']]],
    );

    expect($addable->filter('.sy-fo-repeater-add')->count())->toBe(1)
        ->and($addable->filter('.sy-fo-repeater-add button')->text())->toContain('Add to tags');

    $full = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->maxItems(2),
        ['tags' => [['name' => 'a'], ['name' => 'b']]],
    );

    expect($full->filter('.sy-fo-repeater-add')->count())->toBe(0);
});

it('aligns the add trigger container when an alignment is declared', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')])->addActionAlignment('end'),
        ['tags' => []],
    );

    expect($crawler->filter('.sy-fo-repeater-add')->attr('class'))->toContain('sy-align-end');
});

it('renders no item list but keeps the add trigger while empty', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->components([TextInput::make('name')]),
        ['tags' => []],
    );

    expect($crawler->filter('.sy-fo-repeater-items')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-item')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-repeater-add')->count())->toBe(1);
});

it('renders the simple variant without a label on the inner field', function (): void {
    $crawler = renderRepeater(
        Repeater::make('tags')->simple(TextInput::make('value')),
        ['tags' => ['a', 'b']],
    );

    expect($crawler->filter('.sy-fo-simple-repeater')->count())->toBe(1)
        ->and($crawler->filter('.sy-fo-repeater')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-simple-repeater-item')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-simple-repeater-item input')->count())->toBe(2)
        ->and($crawler->filter('.sy-fo-simple-repeater-item .sy-fo-field-label:not(.sy-fo-sr-only)')->count())->toBe(0)
        ->and($crawler->filter('.sy-fo-simple-repeater-item-actions [aria-label="Delete"]')->count())->toBe(2);
});

it('renders an item field error inside that item block', function (): void {
    $component = $this->createLiveComponent('ValidatedRepeaterForm', ['validatedSchemas' => ['schema']]);
    $component->set('data', ['tags' => [['name' => 'a'], ['name' => null]]]);

    $crawler = $component->render()->crawler();

    $items = $crawler->filter('.sy-fo-repeater-item');

    expect($items->count())->toBe(2)
        ->and($items->first()->filter('.sy-fo-field-error-message')->count())->toBe(0)
        ->and($items->last()->filter('.sy-fo-field-error-message')->text())->toBe('This value should not be blank.');
});

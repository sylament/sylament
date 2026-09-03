<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives the tags input through a real round-trip on a shipped-shape
 * #[AsLiveComponent] host. The field binds no element to a model — its text input
 * is scratch state and the controller writes the whole list with
 * `component.set(statePath, [...tags])` — so `set()` here plays exactly the write
 * the browser makes, and these cases pin the half the port owns: the list rests as
 * an array, a `separator()` field folds it onto a string at the dehydration
 * boundary and explodes it back on hydration, and a `live()` field's
 * `afterStateUpdated` receives the list rather than a scalar.
 */

it('rests every arm at a list, not null', function (): void {
    expect($this->createLiveComponent('TagsInputForm')->component()->data)
        ->toBe(['tags' => [], 'keywords' => [], 'topics' => []]);
});

it('round-trips the whole list the controller writes', function (): void {
    $component = $this->createLiveComponent('TagsInputForm')->set('data.tags', ['a', 'b']);

    expect($component->component()->data['tags'])->toBe(['a', 'b']);

    $chips = $component->render()->crawler()
        ->filter('div[id="schema.tags-tags"] > span.sy-badge');

    expect($chips->extract(['data-value']))->toBe(['a', 'b']);

    // A second write replaces the list wholesale — the controller never sends a delta.
    $chips = $component->set('data.tags', ['b'])->render()->crawler()
        ->filter('div[id="schema.tags-tags"] > span.sy-badge');

    expect($chips->extract(['data-value']))->toBe(['b']);
});

it('joins a separator field onto a string when the state is dehydrated', function (): void {
    $component = $this->createLiveComponent('TagsInputForm')
        ->set('data.keywords', ['a', 'b'])
        ->call('save');

    expect($component->component()->savedState['keywords'])->toBe('a,b')
        ->and($component->component()->savedState['tags'])->toBe([]);
});

it('dehydrates an emptied separator field to an empty string', function (): void {
    // The `''` → null normalisation runs before the dehydration closure, so an empty
    // list implodes to `''` rather than resting at null — Filament's exact behaviour.
    $component = $this->createLiveComponent('TagsInputForm')
        ->set('data.keywords', [])
        ->call('save');

    expect($component->component()->savedState['keywords'])->toBe('');
});

it('explodes a separator-joined string back into a list on hydration', function (): void {
    // A record holds the joined string; `hydrateTags()` runs off `afterStateHydrated`,
    // so the list only appears once the bag is filled — never on a bare `set()`.
    $component = $this->createLiveComponent('TagsInputForm', ['data' => ['keywords' => 'a,b']]);

    expect($component->component()->data['keywords'])->toBe(['a', 'b']);

    $chips = $component->render()->crawler()->filter('div[id="schema.keywords-tags"] > span.sy-badge');

    expect($chips->extract(['data-value']))->toBe(['a', 'b']);
});

it('hands the list to a live field afterStateUpdated hook', function (): void {
    $component = $this->createLiveComponent('TagsInputForm')->set('data.topics', ['php', 'js']);

    expect($component->component()->updatedTopics)->toBe(['php', 'js']);
});

it('renders no data-model anywhere in the field', function (): void {
    $html = (string) $this->createLiveComponent('TagsInputForm')->render();

    expect(new Crawler($html)->filter('div.sy-fo-tags-input [data-model]')->count())->toBe(0);
});

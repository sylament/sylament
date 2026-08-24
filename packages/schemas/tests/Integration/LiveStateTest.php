<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('binds each field with its data-model modifier', function (): void {
    $html = (string) $this->createLiveComponent('LiveStateHost')->render();

    expect($html)
        ->toContain('data-model="data.title"')                  // live() -> bare path
        ->toContain('data-model="norender|data.slug"')          // default -> syncs, no re-render
        ->toContain('data-model="on(change)|data.heading"')     // lazy() -> blur/change
        ->toContain('data-model="debounce(250)|data.excerpt"'); // debounce(250)
});

it('fires afterStateUpdated with the old value when a bound field changes', function (): void {
    $crawler = $this->createLiveComponent('LiveStateHost')
        ->set('data.title', 'Hello')
        ->set('data.title', 'World')
        ->render()
        ->crawler();

    // title now holds the new value; its afterStateUpdated mirrored the previous
    // value ("Hello") into the sibling slug — proving the hook fired on the
    // round-trip, was passed the old value, and the change re-rendered.
    expect($crawler->filter('input[data-model="data.title"]')->attr('value'))->toBe('World')
        ->and($crawler->filter('input[data-model="norender|data.slug"]')->attr('value'))->toBe('Hello');
});

it('does not fire afterStateUpdated when the value is unchanged', function (): void {
    $slug = $this->createLiveComponent('LiveStateHost')
        ->set('data.title', 'Hello')
        ->set('data.title', 'World')
        ->set('data.title', 'World') // unchanged -> must not re-run the hook
        ->render()
        ->crawler()
        ->filter('input[data-model="norender|data.slug"]')
        ->attr('value');

    // A firing hook would mirror the now-current "World" into slug; the unchanged
    // set leaves slug at the previous change's old value.
    expect($slug)->toBe('Hello');
});

it('assembles getState from the bag on a live action', function (): void {
    $component = $this->createLiveComponent('LiveStateHost')
        ->set('data.title', 'Hello')
        ->call('save');

    expect($component->component()->savedState)->toBe([
        'title' => 'Hello',
        'slug' => null,
        'heading' => null,
        'excerpt' => null,
        'upload' => null,
    ]);
});

<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives the per-option `data-model` binding through a real round-trip on a
 * shipped-shape #[AsLiveComponent] host — the only way to settle how N inputs
 * sharing one model behave. Filament puts one `wire:model` on every radio; the
 * port puts one `data-model` on every radio.
 *
 * These cases pin the server half, which is the half the port owns: every input
 * declares the same path, `set()` plays the write a browser makes (an unchecked
 * radio fires no event, so `live_controller`'s `getValueFromElement()` only ever
 * reads the clicked one's `value`), and the re-render must move `checked` onto
 * the option that now matches. The client half needs no test because it is not
 * ours: `SetValueOntoModelFieldsPlugin` walks `[data-model]` after every render
 * and calls `setValueOnElement()`, whose radio arm is the same comparison —
 * `element.checked = element.value == value`.
 */

it('binds every radio to the one state path', function (): void {
    $html = (string) $this->createLiveComponent('RadioForm')->render();

    $inputs = new Crawler($html)->filter('input.sy-fo-radio-input');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('data-model'))->toBe('norender|data.status')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.status')
        ->and($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('moves checked onto the option a round-trip selects', function (): void {
    $component = $this->createLiveComponent('RadioForm')->set('data.status', 'published');

    expect($component->component()->data)->toBe(['status' => 'published']);

    $inputs = $component->render()->crawler()->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    // Re-selecting moves the attribute rather than adding a second one: the shared
    // model holds exactly one value, so exactly one input can match it.
    $inputs = $component->set('data.status', 'draft')->render()->crawler()->filter('input.sy-fo-radio-input');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

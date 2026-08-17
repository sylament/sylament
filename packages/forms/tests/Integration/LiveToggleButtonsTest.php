<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives both `ToggleButtons` bindings through a real round-trip on a
 * shipped-shape #[AsLiveComponent] host, because the two arms are one
 * `data-model` per input with different client semantics behind it, and only a
 * round-trip settles which one each gets.
 *
 * The single arm is Radio's: N radio inputs on one path, `live_controller`'s
 * `getValueFromElement()` falling through to `element.value` for the clicked one.
 * The multiple arm is the one worth pinning: that function's checkbox branch
 * reads the model's *current* value first and only calls
 * `getMultipleCheckboxValue()` — add-or-remove against the list — when it finds
 * an array or an object there. A path resting at `null` would therefore take the
 * clicked option's bare value and collapse the list to a scalar. It cannot,
 * because `OptionsArrayStateCast(isNullable: false)` writes `[]` rather than
 * null, and the test below asserts that resting shape on the wire before it
 * asserts anything about checking.
 */

it('rests the multiple arm at an array, not null', function (): void {
    expect($this->createLiveComponent('ToggleButtonsForm')->component()->data)
        ->toBe(['status' => null, 'tags' => []]);
});

it('binds every input of a field to that one state path', function (): void {
    $crawler = new Crawler((string) $this->createLiveComponent('ToggleButtonsForm')->render());

    $single = $crawler->filter('div.sy-fo-toggle-buttons[role="radiogroup"] input');
    $multiple = $crawler->filter('div.sy-fo-toggle-buttons[role="group"] input');

    expect($single->count())->toBe(2)
        ->and($single->eq(0)->attr('type'))->toBe('radio')
        ->and($single->eq(0)->attr('data-model'))->toBe('norender|data.status')
        ->and($single->eq(1)->attr('data-model'))->toBe('norender|data.status')
        ->and($multiple->count())->toBe(2)
        ->and($multiple->eq(0)->attr('type'))->toBe('checkbox')
        ->and($multiple->eq(0)->attr('data-model'))->toBe('norender|data.tags')
        ->and($multiple->eq(1)->attr('data-model'))->toBe('norender|data.tags');
});

it('moves checked onto the single option a round-trip selects', function (): void {
    $component = $this->createLiveComponent('ToggleButtonsForm')->set('data.status', 'published');

    expect($component->component()->data['status'])->toBe('published');

    $inputs = $component->render()->crawler()->filter('div[role="radiogroup"] input');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    $inputs = $component->set('data.status', 'draft')->render()->crawler()->filter('div[role="radiogroup"] input');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

it('checks every member of the list a multiple round-trip writes', function (): void {
    $component = $this->createLiveComponent('ToggleButtonsForm')->set('data.tags', ['b']);

    expect($component->component()->data['tags'])->toBe(['b']);

    $inputs = $component->render()->crawler()->filter('div[role="group"] input');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    // The client sends the whole list, not a delta, so a second selection arrives
    // as both members and both inputs must come back checked.
    $inputs = $component->set('data.tags', ['b', 'a'])->render()->crawler()->filter('div[role="group"] input');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    $inputs = $component->set('data.tags', [])->render()->crawler()->filter('div[role="group"] input');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

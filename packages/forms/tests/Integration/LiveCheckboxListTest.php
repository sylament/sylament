<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Integration;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * Drives the checkbox-array binding through a real round-trip on a shipped-shape
 * #[AsLiveComponent] host, the same discipline `LiveToggleButtonsTest` pins for
 * its multiple arm: `live_controller`'s `getValueFromElement()` reads the model's
 * *current* value first and only adds-or-removes against the list when it finds
 * an array there, so a path resting at `null` would collapse the list to the
 * clicked option's bare value. `OptionsArrayStateCast` writes `[]` rather than
 * null, and the first case below asserts that resting shape on the wire before
 * anything else asserts checking.
 */

it('rests the list at an array, not null', function (): void {
    expect($this->createLiveComponent('CheckboxListForm')->component()->data)
        ->toBe(['topics' => []]);
});

it('binds every checkbox of the field to that one state path', function (): void {
    $inputs = new Crawler((string) $this->createLiveComponent('CheckboxListForm')->render())
        ->filter('div.sy-fo-checkbox-list input.sy-fo-checkbox');

    expect($inputs->count())->toBe(2)
        ->and($inputs->eq(0)->attr('type'))->toBe('checkbox')
        ->and($inputs->eq(0)->attr('data-model'))->toBe('norender|data.topics')
        ->and($inputs->eq(1)->attr('data-model'))->toBe('norender|data.topics');
});

it('checks every member of the list a round-trip writes', function (): void {
    $component = $this->createLiveComponent('CheckboxListForm')->set('data.topics', ['js']);

    expect($component->component()->data['topics'])->toBe(['js']);

    $inputs = $component->render()->crawler()->filter('input.sy-fo-checkbox');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    // The client sends the whole list, not a delta, so a second selection arrives
    // as both members and both inputs must come back checked.
    $inputs = $component->set('data.topics', ['js', 'php'])->render()->crawler()->filter('input.sy-fo-checkbox');

    expect($inputs->eq(0)->attr('checked'))->not->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->not->toBeNull();

    $inputs = $component->set('data.topics', [])->render()->crawler()->filter('input.sy-fo-checkbox');

    expect($inputs->eq(0)->attr('checked'))->toBeNull()
        ->and($inputs->eq(1)->attr('checked'))->toBeNull();
});

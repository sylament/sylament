<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use LogicException;
use Sylament\Schemas\Components\LiveComponent;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;

it('holds the component name and its data', function (): void {
    $embed = LiveComponent::make('ChildComponent', ['a' => 1]);

    expect($embed->getComponent())->toBe('ChildComponent')
        ->and($embed->getData())->toBe(['a' => 1]);
});

it('defaults to no data', function (): void {
    expect(LiveComponent::make('ChildComponent')->getData())->toBe([]);
});

it('evaluates a component-name closure with the component injection', function (): void {
    $embed = LiveComponent::make(fn (LiveComponent $component): string => $component->getKey(isAbsolute: false) . 'Component');

    expect($embed->key('Child')->getComponent())->toBe('ChildComponent');
});

it('evaluates a data closure with the component injection', function (): void {
    $embed = LiveComponent::make(
        'ChildComponent',
        fn (LiveComponent $component): array => ['key' => $component->getKey(isAbsolute: false)],
    );

    expect($embed->key('embed')->getData())->toBe(['key' => 'embed']);
});

it('mounts the child with its data and no implicit record prop', function (): void {
    // UX's hydrator refuses an unknown prop where Livewire ignores it, so the
    // embed never adds Filament's implicit `record`.
    expect(LiveComponent::make('ChildComponent', ['a' => 1])->getComponentProperties())
        ->toBe(['a' => 1]);
});

it('derives the child id from the live-component key', function (): void {
    $host = new HasSchemasFixture();
    $embed = LiveComponent::make('ChildComponent')->key('embed');
    Schema::make($host)->components([$embed])->getComponents();

    expect($embed->getChildId())->toBe('sy-live-' . $host->getSchemasHostId() . '-embed');
});

it('derives distinct child ids for distinct keys under one host', function (): void {
    $host = new HasSchemasFixture();
    $first = LiveComponent::make('ChildComponent')->key('first');
    $second = LiveComponent::make('ChildComponent')->key('second');
    Schema::make($host)->components([$first, $second])->getComponents();

    expect($first->getChildId())->not->toBe($second->getChildId());
});

it('falls back to the bare key when the embed has no live host', function (): void {
    $embed = LiveComponent::make('ChildComponent')->key('embed');
    Schema::make()->components([$embed])->getComponents();

    expect($embed->getChildId())->toBe('sy-live-embed');
});

it('throws when a keyless embed is asked for its child id', function (): void {
    $embed = LiveComponent::make('ChildComponent');
    Schema::make()->components([$embed])->getComponents();

    expect(fn (): string => $embed->getChildId())
        ->toThrow(LogicException::class, 'key()');
});

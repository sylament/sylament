<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

it('mounts each embedded child once, with its own data', function (): void {
    $crawler = $this->createLiveComponent('EmbedHost')->render()->crawler();

    $children = $crawler->filter('.embedded-child');

    expect($children->count())->toBe(2)
        ->and($children->eq(0)->text())->toBe('first')
        ->and($children->eq(1)->text())->toBe('second');
});

it('gives each embed the id derived from its own key', function (): void {
    $component = $this->createLiveComponent('EmbedHost');

    $ids = $component->render()->crawler()->filter('.embedded-child')->extract(['id']);
    $hostId = $component->component()->getSchemasHostId();

    expect($ids)->toBe([
        'sy-live-' . $hostId . '-schema-first',
        'sy-live-' . $hostId . '-schema-second',
    ]);
});

it('keeps each child id stable across a live round trip', function (): void {
    $component = $this->createLiveComponent('EmbedHost');

    $first = $component->render()->crawler()->filter('.embedded-child')->extract(['id']);
    $second = $component->refresh()->render()->crawler()->filter('.embedded-child')->extract(['id']);

    expect($second)->toBe($first);
});

it('mounts each child as a live component of its own', function (): void {
    $roots = $this->createLiveComponent('EmbedHost')
        ->render()
        ->crawler()
        ->filter('.embedded-child[data-controller~="live"]');

    expect($roots->count())->toBe(2);
});

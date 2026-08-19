<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Integration\Components;

use Sylament\Schemas\Components\Tabs;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Concerns\HasSchemasFixture;
use Sylament\Support\Colors\Color;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Enums\IconPosition;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/**
 * The deferred-badge half of Tabs goes through the host seam: the client controller asks
 * the host for `getDeferredTabBadges()` by component key and fills the placeholders from
 * the answer. This drives that call the way the browser does.
 */
it('answers the deferred badges keyed by tab key through the host seam', function (): void {
    $component = $this->createLiveComponent('TabsDeferredBadgeHost');
    $component->call('callSchemaComponentMethod', [
        'componentKey' => 'schema.t::tabs',
        'method' => 'getDeferredTabBadges',
        'arguments' => [],
    ]);

    $event = $component->getDispatchedBrowserEvent($component->render(), 'sylament:schema-component-method-result');

    $dangerClasses = implode(' ', $this::getContainer()->get(ColorManager::class)
        ->getComponentClasses(BadgeComponent::class, 'danger'));

    expect($event)->not->toBeNull()
        ->and($event['payload']['componentKey'])->toBe('schema.t::tabs')
        ->and($event['payload']['method'])->toBe('getDeferredTabBadges')
        // Only the deferred tab answers, keyed by its own non-absolute key.
        ->and($event['payload']['result'])->toBe([
            'a::tab' => [
                'badge' => '7',
                'badgeColorClasses' => $dangerClasses,
                'badgeColorStyles' => '',
                'badgeIconHtml' => null,
                'badgeIconPosition' => 'before',
                'badgeTooltip' => null,
            ],
        ]);
});

it('emits inline styles for an array colour and renders the badge icon server-side', function (): void {
    // Boot first: `make()` reaches the container through the ComponentResolver static
    // instance, which only exists once the kernel has booted — without it the Tabs is
    // built by `new` and carries neither the icon extension nor the colour manager.
    $colors = $this::getContainer()->get(ColorManager::class);

    $tabs = Tabs::make('T')->tabs([
        Tab::make('A')
            ->badge('2')
            ->badgeColor(Color::Emerald)
            ->badgeIcon('heroicons:star')
            ->badgeIconPosition(IconPosition::After)
            ->badgeTooltip('Two')
            ->deferBadge(),
    ]);
    Schema::make(new HasSchemasFixture())->components([$tabs])->getComponents();

    $badge = $tabs->getDeferredTabBadges()['a::tab'];

    $styles = implode('; ', $colors->getComponentCustomStyles(BadgeComponent::class, Color::Emerald));

    expect($badge['badgeColorClasses'])->toBe('sy-color')
        ->and($badge['badgeColorStyles'])->toBe($styles)
        ->and($badge['badgeIconPosition'])->toBe('after')
        ->and($badge['badgeTooltip'])->toBe('Two')
        ->and($badge['badgeIconHtml'])->toContain('<svg')
        ->and($badge['badgeIconHtml'])->toContain('sy-size-sm');
});

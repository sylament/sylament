<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\Size;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;

it('renders a span, a link to its href in a new tab only when asked for one, and a button with neither', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', content: 'Urgent')->crawler())
        ->toContainElement('span' . SupportSelectors::BADGE, 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'a', 'href' => '/orders', 'target' => '_blank'], content: 'Urgent')->crawler())
        ->toContainElement('a' . SupportSelectors::BADGE . '[href="/orders"][target="_blank"]', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'a', 'href' => '/orders', 'target' => '_self'], content: 'Urgent')->crawler())
        ->toContainElement('a' . SupportSelectors::BADGE . '[href="/orders"]:not([target])', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'button', 'href' => '/orders', 'target' => '_blank'], content: 'Urgent')->crawler())
        ->toContainElement('button' . SupportSelectors::BADGE . ':not([href]):not([target])', 'Urgent');
});

it('takes a type and names its form only when tagged button', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'button'], content: 'Urgent')->crawler())
        ->toContainElement('button' . SupportSelectors::BADGE . '[type="button"]:not([form])', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'button', 'type' => 'submit', 'formId' => 'order-form'], content: 'Urgent')->crawler())
        ->toContainElement('button' . SupportSelectors::BADGE . '[type="submit"][form="order-form"]', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['type' => 'submit', 'formId' => 'order-form'], content: 'Urgent')->crawler())
        ->toContainElement('span' . SupportSelectors::BADGE . ':not([type]):not([form])', 'Urgent');
});

it('disables a button with the disabled attribute and a span or a link without it, and marks none when enabled', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'button', 'disabled' => true], content: 'Urgent')->crawler())
        ->toContainElement('button' . SupportSelectors::BADGE . SupportSelectors::DISABLED . '[aria-disabled="true"][disabled]', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['disabled' => true], content: 'Urgent')->crawler())
        ->toContainElement('span' . SupportSelectors::BADGE . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'a', 'href' => '/orders', 'disabled' => true], content: 'Urgent')->crawler())
        ->toContainElement('a' . SupportSelectors::BADGE . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['tag' => 'button'], content: 'Urgent')->crawler())
        ->toContainElement('button' . SupportSelectors::BADGE . ':not(' . SupportSelectors::DISABLED . '):not([aria-disabled]):not([disabled])', 'Urgent');
});

it('renders at the medium size unless sized, and a size outside the scale as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . SupportSelectors::MEDIUM, 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['size' => Size::Large], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . SupportSelectors::size('lg'), 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['size' => 'huge'], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . '.huge' . SupportSelectors::UNSIZED, 'Urgent');
});

it("paints in primary unless coloured, gray uncoloured, and a shade map inline beside the caller's style", function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . SupportSelectors::COLORED . SupportSelectors::color('primary') . SupportSelectors::textShades(700, 400), 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['color' => 'gray'], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . SupportSelectors::GRAY, 'Urgent')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['color' => Color::Rose, 'style' => 'width: 2rem'], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]) . '[style$="width: 2rem"]', 'Urgent');
});

it('draws a small icon before or after its label, at the size it is given', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', ['icon' => 'support:star'], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE, 'StarUrgent')
        ->toContainElement(SupportSelectors::BADGE . ' ' . SupportSelectors::ICON . SupportSelectors::size('sm'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['icon' => 'support:star', 'iconPosition' => IconPosition::After, 'iconSize' => 'lg'], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE, 'UrgentStar')
        ->toContainElement(SupportSelectors::BADGE . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star');
});

it('wraps its label in a container span', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_LABEL_CONTAINER . ' ' . SupportSelectors::BADGE_LABEL, 'Urgent');
});

it('is named by its label stripped of markup when the label is hidden, and by its text otherwise', function (): void {
    $hidden = $this->renderTwigComponent('SylamentSupport:Badge', ['labelSrOnly' => true], content: ' <b>Urgent</b> ')->crawler();

    expect($hidden)->toContainElement(SupportSelectors::BADGE . '[aria-label="Urgent"]')
        ->and($hidden->filter(SupportSelectors::BADGE_LABEL_CONTAINER . ', ' . SupportSelectors::BADGE_LABEL))->toHaveCount(0)
        ->and($this->renderTwigComponent('SylamentSupport:Badge', content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE . ':not([aria-label]) ' . SupportSelectors::BADGE_LABEL, 'Urgent');
});

it('offers a button after its label that removes it, named Remove unless named otherwise, carrying the caller attributes', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Badge', ['deletable' => true], content: 'Urgent')->crawler())
        ->toContainElement(SupportSelectors::BADGE, 'Urgentx-mark-16-solid')
        ->toContainElement(SupportSelectors::BADGE . ' ' . SupportSelectors::BADGE_DELETE_BUTTON . '[type="button"] svg' . SupportSelectors::ICON . SupportSelectors::size('xs') . '[aria-hidden="true"]', 'x-mark-16-solid')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['deletable' => true, 'deleteLabel' => ' <b>Discard</b> ', 'deleteAttributes' => ['class' => 'js-discard', 'data-tag' => 'urgent']], content: 'Urgent')->crawler())
        ->toContainElement('button.sy-badge-delete-btn.js-discard[aria-label="Discard"][data-tag="urgent"]', 'x-mark-16-solid')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['deletable' => true, 'deleteAttributes' => ['aria-label' => 'Remove tag']], content: 'Urgent')->crawler())
        ->toContainElement('button.sy-badge-delete-btn[aria-label="Remove tag"]', 'x-mark-16-solid')
        ->and($this->renderTwigComponent('SylamentSupport:Badge', ['deletable' => true, 'deleteLabel' => ''], content: 'Urgent')->crawler())
        ->toContainElement('button.sy-badge-delete-btn:not([aria-label])', 'x-mark-16-solid');
});

it('shows its remove button in place of an icon positioned after its label', function (): void {
    $badge = $this->renderTwigComponent('SylamentSupport:Badge', ['deletable' => true, 'icon' => 'support:star', 'iconPosition' => IconPosition::After], content: 'Urgent')->crawler();

    expect($badge)->toContainElement(SupportSelectors::BADGE, 'Urgentx-mark-16-solid')
        ->and($badge->filter(SupportSelectors::ICON)->each(fn (Crawler $icon): string => $icon->text()))->toBe(['x-mark-16-solid']);
});

<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\FontWeight;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\Size;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders a link to its href, in a new tab only when asked for one, and a button with neither', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders', 'target' => '_blank'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . '[href="/orders"][target="_blank"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders', 'target' => '_self'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . '[href="/orders"]:not([target])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['tag' => 'button', 'href' => '/orders', 'target' => '_blank'], content: 'Orders')->crawler())
        ->toContainElement('button' . SupportSelectors::LINK . ':not([href]):not([target])', 'Orders');
});

it('is a plain button unless typed submit when tagged button, and names its form whatever its tag', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['tag' => 'button'], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::LINK . '[type="button"]:not([form])', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['tag' => 'button', 'type' => 'submit', 'formId' => 'order-form'], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::LINK . '[type="submit"][form="order-form"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders', 'type' => 'submit', 'formId' => 'order-form'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . '[form="order-form"]:not([type])', 'Orders');
});

it('disables a button with the disabled attribute and a link without it, and marks neither when enabled', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['tag' => 'button', 'disabled' => true], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::LINK . SupportSelectors::DISABLED . '[aria-disabled="true"][disabled]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders', 'disabled' => true], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . ':not(' . SupportSelectors::DISABLED . '):not([aria-disabled]):not([disabled])', 'Orders');
});

it('renders at the medium size unless sized, and a size outside the scale as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . SupportSelectors::MEDIUM, 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['size' => Size::Large], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . SupportSelectors::size('lg'), 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['size' => 'huge'], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . '.huge:not([class*="sy-size-"])', 'Orders');
});

it('sets its font weight when given one, and a weight outside the scale as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['weight' => FontWeight::Bold], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . '.sy-font-bold', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['weight' => 'heavy'], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . '.heavy:not([class*="sy-font-"])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . ':not([class*="sy-font-"])', 'Orders');
});

it("paints in primary unless coloured, gray uncoloured, and a shade map inline beside the caller's style", function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . SupportSelectors::COLORED . SupportSelectors::color('primary') . '[class~="sy-text-color-700"][class~="dark:sy-text-color-400"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['color' => 'gray'], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . SupportSelectors::GRAY . ':not([class*="color"])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['color' => Color::Rose, 'style' => 'width: 2rem'], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK . SupportSelectors::COLORED . '[style*="--sy-color-500: ' . Color::Rose[500] . ';"][style$="width: 2rem"]', 'Orders');
});

it('draws its icon before or after its label', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['icon' => 'support:star'], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK, 'StarOrders')
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['icon' => 'support:star', 'iconPosition' => IconPosition::After], content: 'Orders')->crawler())
        ->toContainElement(SupportSelectors::LINK, 'OrdersStar');
});

it('draws its icon small on a small link unless given a size, and at the medium size otherwise', function (array $props, string $icon): void {
    expect($this->renderTwigComponent('SylamentSupport:Link', ['icon' => 'support:star', ...$props], content: 'Orders')->crawler())
        ->toContainElement($icon, 'Star');
})->with([
    'a small icon on a small link' => [['size' => 'sm'], SupportSelectors::SMALL_LINK . ' ' . SupportSelectors::ICON . SupportSelectors::size('sm')],
    'a medium icon on a large link' => [['size' => 'lg'], SupportSelectors::LINK . SupportSelectors::size('lg') . ' ' . SupportSelectors::ICON . SupportSelectors::MEDIUM],
    "the given icon size over the link's" => [['size' => 'sm', 'iconSize' => 'lg'], SupportSelectors::SMALL_LINK . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg')],
]);

it('is named by its label stripped of markup when the label is hidden, and by its text otherwise', function (): void {
    $hidden = $this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders', 'labelSrOnly' => true], content: ' <b>Orders</b> ')->crawler();

    expect($hidden)->toContainElement('a' . SupportSelectors::LINK . '[aria-label="Orders"]')
        ->and($hidden->filter('.sy-link-label'))->toHaveCount(0)
        ->and($this->renderTwigComponent('SylamentSupport:Link', ['href' => '/orders'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::LINK . ':not([aria-label]) .sy-link-label', 'Orders');
});

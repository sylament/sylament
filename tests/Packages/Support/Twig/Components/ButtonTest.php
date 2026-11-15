<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Enums\Size;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders a button, and a link to its href when tagged a, in a new tab only when asked for one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', ['href' => '/orders', 'target' => '_blank'], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::BUTTON . ':not([href]):not([target])', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['tag' => 'a', 'href' => '/orders', 'target' => '_blank'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::BUTTON . '[href="/orders"][target="_blank"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['tag' => 'a', 'href' => '/orders', 'target' => '_self'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::BUTTON . '[href="/orders"]:not([target])', 'Orders');
});

it('is a plain button unless typed submit, and a link takes no type', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::BUTTON . '[type="button"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['type' => 'submit'], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::BUTTON . '[type="submit"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['tag' => 'a', 'href' => '/orders', 'type' => 'submit'], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::BUTTON . ':not([type])', 'Orders');
});

it('belongs to the form it names, and to none unless it names one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', ['type' => 'submit', 'formId' => 'order-form'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . '[form="order-form"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['type' => 'submit'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . ':not([form])', 'Save');
});

it('disables a button with the disabled attribute and a link without it, and marks neither when enabled', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', ['disabled' => true], content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::BUTTON . SupportSelectors::DISABLED . '[aria-disabled="true"][disabled]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['tag' => 'a', 'href' => '/orders', 'disabled' => true], content: 'Orders')->crawler())
        ->toContainElement('a' . SupportSelectors::BUTTON . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Button', content: 'Save')->crawler())
        ->toContainElement('button' . SupportSelectors::BUTTON . ':not(' . SupportSelectors::DISABLED . '):not([aria-disabled]):not([disabled])', 'Save');
});

it('renders at the medium size unless sized, and a size outside the scale as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::MEDIUM, 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['size' => Size::Large], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::size('lg'), 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['size' => 'huge'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . '.huge:not([class*="sy-size-"])', 'Save');
});

it("paints in primary unless coloured, gray uncoloured, and a shade map inline beside the caller's style", function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::COLORED . SupportSelectors::color('primary') . '[class~="sy-bg-color-400"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['color' => 'gray'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::GRAY . ':not([class*="color"])', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['color' => Color::Rose, 'style' => 'width: 2rem'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::COLORED . '[style*="--sy-color-500: ' . Color::Rose[500] . ';"][style$="width: 2rem"]', 'Save');
});

it('paints an outlined button in its text shades alone and a filled one with its backgrounds', function (): void {
    $outlined = $this->renderTwigComponent('SylamentSupport:Button', ['color' => 'primary', 'outlined' => true], content: 'Save')->crawler();
    $filled = $this->renderTwigComponent('SylamentSupport:Button', ['color' => 'primary'], content: 'Save')->crawler();

    expect($outlined)
        ->toContainElement(SupportSelectors::BUTTON . '.sy-outlined' . SupportSelectors::color('primary') . '[class~="dark:sy-text-color-500"]', 'Save')
        ->not->toContainElement('[class*="sy-bg-color-"]')
        ->and($filled)
        ->toContainElement(SupportSelectors::BUTTON . SupportSelectors::color('primary') . ':not(.sy-outlined)[class~="hover:sy-bg-color-300"]', 'Save');
});

it('draws its icon before its label, at the size it is given', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', ['icon' => 'support:star'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON, 'StarSave')
        ->and($this->renderTwigComponent('SylamentSupport:Button', ['icon' => 'support:star', 'iconSize' => 'lg'], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star');
});

it('draws its icon after its label when positioned after', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Button', ['icon' => 'support:star', 'iconPosition' => IconPosition::After], content: 'Save')->crawler())
        ->toContainElement(SupportSelectors::BUTTON, 'SaveStar');
});

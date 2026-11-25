<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('renders a medium button, a link when asked, and a size outside the scale as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star'])->crawler())
        ->toContainElement('button' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::ICON_BUTTON . SupportSelectors::MEDIUM, 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'tag' => 'a', 'href' => '/orders'])->crawler())
        ->toContainElement('a' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::ICON_BUTTON . SupportSelectors::MEDIUM, 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'size' => 'huge'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '.huge' . SupportSelectors::UNSIZED, 'Star');
});

it('draws its glyph at the size its button size calls for, unless given one', function (array $props, string $glyph): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', ...$props])->crawler())
        ->toContainElement($glyph, 'Star');
})->with([
    'a small glyph on an extra-small button' => [['size' => 'xs'], SupportSelectors::ICON_BUTTON . SupportSelectors::size('xs') . ' ' . SupportSelectors::ICON . SupportSelectors::size('sm')],
    'a medium glyph on a small button' => [['size' => 'sm'], SupportSelectors::SMALL_ICON_BUTTON . ' ' . SupportSelectors::ICON . SupportSelectors::MEDIUM],
    'a large glyph on a large button' => [['size' => 'lg'], SupportSelectors::ICON_BUTTON . SupportSelectors::size('lg') . ' ' . SupportSelectors::ICON . SupportSelectors::size('lg')],
    "the given glyph size over the button's" => [['size' => 'lg', 'iconSize' => 'xs'], SupportSelectors::ICON_BUTTON . SupportSelectors::size('lg') . ' ' . SupportSelectors::ICON . SupportSelectors::size('xs')],
]);

it('is named by the text of its label, titled by it, and neither without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'label' => 'Favourite'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '[aria-label="Favourite"][title="Favourite"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'label' => ' <b>Favourite</b> '])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '[aria-label="Favourite"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'label' => new Markup('<b>Salt</b> &amp; pepper', 'UTF-8')])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '[aria-label="Salt & pepper"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . ':not([aria-label]):not([title])', 'Star');
});

it('disables a button with the disabled attribute and a link without it, and marks neither when enabled', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'disabled' => true])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . SupportSelectors::DISABLED . '[aria-disabled="true"][disabled]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'tag' => 'a', 'href' => '/orders', 'disabled' => true])->crawler())
        ->toContainElement('a' . SupportSelectors::ICON_BUTTON . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . ':not(' . SupportSelectors::DISABLED . '):not([aria-disabled]):not([disabled])', 'Star');
});

it('links to its href, in a new tab only when asked for one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'tag' => 'a', 'href' => '/orders', 'target' => '_blank'])->crawler())
        ->toContainElement('a' . SupportSelectors::ICON_BUTTON . '[href="/orders"][target="_blank"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'tag' => 'a', 'href' => '/orders', 'target' => '_self'])->crawler())
        ->toContainElement('a' . SupportSelectors::ICON_BUTTON . '[href="/orders"]:not([target])', 'Star');
});

it('submits the form it names when its type is submit, and a link takes no type', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '[type="button"]:not([form])', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'type' => 'submit', 'formId' => 'order-form'])->crawler())
        ->toContainElement('button' . SupportSelectors::ICON_BUTTON . '[type="submit"][form="order-form"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'tag' => 'a', 'href' => '/orders', 'type' => 'submit'])->crawler())
        ->toContainElement('a' . SupportSelectors::ICON_BUTTON . ':not([type])', 'Star');
});

it("paints in primary unless coloured, gray uncoloured, and a shade map inline beside the caller's style", function (): void {
    expect($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star'])->crawler())
        ->toContainElement(SupportSelectors::ICON_BUTTON . SupportSelectors::color('primary'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'color' => 'gray'])->crawler())
        ->toContainElement(SupportSelectors::ICON_BUTTON . SupportSelectors::GRAY, 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:IconButton', ['icon' => 'support:star', 'color' => Color::Rose, 'style' => 'width: 2rem'])->crawler())
        ->toContainElement(SupportSelectors::ICON_BUTTON . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]) . '[style$="width: 2rem"]', 'Star');
});

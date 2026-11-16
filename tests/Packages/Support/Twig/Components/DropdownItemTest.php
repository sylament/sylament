<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('renders a button, and a link to its href when tagged a, in a new tab only when asked for one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', ['href' => '/orders/1/edit', 'target' => '_blank'], content: 'Edit')->crawler())
        ->toContainElement('button.sy.sy-dropdown-item:not([href]):not([target])', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['tag' => 'a', 'href' => '/orders/1/edit', 'target' => '_blank'], content: 'Edit')->crawler())
        ->toContainElement('a.sy.sy-dropdown-item[href="/orders/1/edit"][target="_blank"]', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['tag' => 'a', 'href' => '/orders/1/edit', 'target' => '_self'], content: 'Edit')->crawler())
        ->toContainElement('a.sy.sy-dropdown-item[href="/orders/1/edit"]:not([target])', 'Edit');
});

it('is a plain button unless typed submit, submitting the form it names, and a link takes no type', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', content: 'Edit')->crawler())
        ->toContainElement('button.sy-dropdown-item[type="button"]:not([form])', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['type' => 'submit', 'formId' => 'order-form'], content: 'Save')->crawler())
        ->toContainElement('button.sy-dropdown-item[type="submit"][form="order-form"]', 'Save')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['tag' => 'a', 'href' => '/orders/1/edit', 'type' => 'submit', 'formId' => ''], content: 'Edit')->crawler())
        ->toContainElement('a.sy-dropdown-item:not([type]):not([form])', 'Edit');
});

it('disables a button with the disabled attribute and a link without it, and marks neither when enabled', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', ['disabled' => true], content: 'Edit')->crawler())
        ->toContainElement('button.sy-dropdown-item' . SupportSelectors::DISABLED . '[aria-disabled="true"][disabled]', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['tag' => 'a', 'href' => '/orders/1/edit', 'disabled' => true], content: 'Edit')->crawler())
        ->toContainElement('a.sy-dropdown-item' . SupportSelectors::DISABLED . '[aria-disabled="true"]:not([disabled])', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', content: 'Edit')->crawler())
        ->toContainElement('button.sy-dropdown-item:not(' . SupportSelectors::DISABLED . '):not([aria-disabled]):not([disabled])', 'Edit');
});

it("is gray unless coloured, a palette by name, and a shade map inline beside the caller's style", function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', content: 'Edit')->crawler())
        ->toContainElement('.sy-dropdown-item' . SupportSelectors::GRAY . ':not([style])', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['color' => 'danger'], content: 'Delete')->crawler())
        ->toContainElement('.sy-dropdown-item' . SupportSelectors::COLORED . SupportSelectors::color('danger'), 'Delete')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['color' => Color::Rose, 'style' => 'width: 2rem'], content: 'Delete')->crawler())
        ->toContainElement('.sy-dropdown-item' . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]) . '[style$="width: 2rem"]', 'Delete');
});

it('draws its icon before its label, at the size it is given, and no icon without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', ['icon' => 'support:star'], content: 'Favourite')->crawler())
        ->toContainElement('.sy-dropdown-item', 'StarFavourite')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', ['icon' => 'support:star', 'iconSize' => 'lg'], content: 'Favourite')->crawler())
        ->toContainElement('.sy-dropdown-item ' . SupportSelectors::ICON . SupportSelectors::size('lg'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:DropdownItem', content: 'Favourite')->crawler())
        ->toContainElement('.sy-dropdown-item', 'Favourite')
        ->not->toContainElement(SupportSelectors::ICON);
});

it('wraps its label in a label span', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:DropdownItem', ['icon' => 'support:star'], content: 'Favourite')->crawler())
        ->toContainElement('.sy-dropdown-item .sy-dropdown-item-label', 'Favourite');
});

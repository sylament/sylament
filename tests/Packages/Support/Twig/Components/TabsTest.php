<?php

declare(strict_types=1);

it('is a navigation around its tabs with no role of its own, contained or vertical only when told', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Tabs', content: '<button type="button">Orders</button>')->crawler())
        ->toContainElement('nav.sy.sy-tabs:not([role]):not(.sy-contained):not(.sy-vertical) button', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:Tabs', ['contained' => true])->crawler())
        ->toContainElement('nav.sy-tabs.sy-contained:not(.sy-vertical)')
        ->and($this->renderTwigComponent('SylamentSupport:Tabs', ['vertical' => true])->crawler())
        ->toContainElement('nav.sy-tabs.sy-vertical:not(.sy-contained)');
});

it('is named by its label, Tabs without one, and by the caller aria-label over both', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Tabs', ['label' => 'Order sections'])->crawler())
        ->toContainElement('nav.sy-tabs[aria-label="Order sections"]')
        ->and($this->renderTwigComponent('SylamentSupport:Tabs')->crawler())
        ->toContainElement('nav.sy-tabs[aria-label="Tabs"]')
        ->and($this->renderTwigComponent('SylamentSupport:Tabs', ['label' => 'Order sections', 'aria-label' => 'Sections'])->crawler())
        ->toContainElement('nav.sy-tabs[aria-label="Sections"]');
});

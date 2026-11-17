<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('toggles a hidden panel of its content from a trigger, placed below its start and flipping unless placed otherwise', function (): void {
    $dropdown = $this->renderTwigComponent('SylamentSupport:Dropdown', ['class' => 'order-menu'], content: '<a href="/orders/1/edit">Edit</a>', blocks: ['trigger' => '<button type="button">Actions</button>'])->crawler();
    $root = 'div' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::DROPDOWN . '.order-menu' . SupportSelectors::DROPDOWN_CONTROLLER;

    expect($dropdown)
        ->toContainElement($root . '[data-sylament--support--dropdown-placement-value="bottom-start"][data-sylament--support--dropdown-offset-value="8"][data-sylament--support--dropdown-flip-value="true"]')
        ->toContainElement($root . ' ' . SupportSelectors::DROPDOWN_TRIGGER . '[data-sylament--support--dropdown-target="trigger"][data-action~="click->sylament--support--dropdown#toggle"] button', 'Actions')
        ->toContainElement($root . ' .sy-dropdown-panel[hidden][data-sylament--support--dropdown-target="panel"]:not([style]) a[href="/orders/1/edit"]', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', ['placement' => 'right-start', 'offset' => 4, 'flip' => false, 'width' => '12rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement('.sy-dropdown[data-sylament--support--dropdown-placement-value="right-start"][data-sylament--support--dropdown-offset-value="4"][data-sylament--support--dropdown-flip-value="false"] .sy-dropdown-panel[style*="--sy-dropdown-width: 12rem"]', 'Edit');
});

it('teleports its panel only when asked', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Dropdown', ['teleport' => true], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement('.sy-dropdown' . SupportSelectors::TELEPORTED_DROPDOWN)
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement('.sy-dropdown' . SupportSelectors::KEPT_DROPDOWN);
});

it('scrolls its panel once capped at a height, and never without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Dropdown', ['maxHeight' => '20rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement('.sy-dropdown-panel[style*="--sy-dropdown-max-height: 20rem"][style*="--sy-dropdown-overflow: auto"]', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', ['width' => '12rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement('.sy-dropdown-panel[style*="--sy-dropdown-width: 12rem"]:not([style*="--sy-dropdown-max-height"]):not([style*="--sy-dropdown-overflow"])', 'Edit');
});

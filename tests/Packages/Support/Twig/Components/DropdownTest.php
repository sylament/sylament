<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('toggles a hidden panel of its content from a trigger, placed below its start and flipping unless placed otherwise', function (): void {
    $dropdown = $this->renderTwigComponent('SylamentSupport:Dropdown', ['class' => 'order-menu'], content: '<a href="/orders/1/edit">Edit</a>', blocks: ['trigger' => '<button type="button">Actions</button>'])->crawler();
    $root = 'div' . SupportSelectors::STANDALONE_ROOT . SupportSelectors::DROPDOWN . '.order-menu' . SupportSelectors::DROPDOWN_CONTROLLER;

    expect($dropdown)
        ->toContainElement($root . SupportSelectors::dropdownValue('placement', 'bottom-start') . SupportSelectors::dropdownValue('offset', '8') . SupportSelectors::dropdownValue('flip', 'true'))
        ->toContainElement($root . ' ' . SupportSelectors::DROPDOWN_TRIGGER . '[data-sylament--support--dropdown-target="trigger"][data-action~="click->sylament--support--dropdown#toggle"] button', 'Actions')
        ->toContainElement($root . ' ' . SupportSelectors::DROPDOWN_PANEL . '[hidden][data-sylament--support--dropdown-target="panel"]:not([style]) a[href="/orders/1/edit"]', 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', ['placement' => 'right-start', 'offset' => 4, 'flip' => false, 'width' => '12rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::dropdownValue('placement', 'right-start') . SupportSelectors::dropdownValue('offset', '4') . SupportSelectors::dropdownValue('flip', 'false') . ' ' . SupportSelectors::DROPDOWN_PANEL . SupportSelectors::dropdownPanelStyle('width', '12rem'), 'Edit');
});

it('teleports its panel only when asked', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Dropdown', ['teleport' => true], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::TELEPORTED_DROPDOWN)
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement(SupportSelectors::DROPDOWN . SupportSelectors::KEPT_DROPDOWN);
});

it('scrolls its panel once capped at a height, and never without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Dropdown', ['maxHeight' => '20rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement(SupportSelectors::DROPDOWN_PANEL . SupportSelectors::dropdownPanelStyle('max-height', '20rem') . SupportSelectors::dropdownPanelStyle('overflow', 'auto'), 'Edit')
        ->and($this->renderTwigComponent('SylamentSupport:Dropdown', ['width' => '12rem'], content: 'Edit', blocks: ['trigger' => 'Actions'])->crawler())
        ->toContainElement(SupportSelectors::DROPDOWN_PANEL . SupportSelectors::dropdownPanelStyle('width', '12rem') . ':not(' . SupportSelectors::dropdownPanelStyle('max-height') . '):not(' . SupportSelectors::dropdownPanelStyle('overflow') . ')', 'Edit');
});

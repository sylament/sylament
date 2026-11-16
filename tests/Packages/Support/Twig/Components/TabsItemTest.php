<?php

declare(strict_types=1);

use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Icons\IconManager;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('is a button of the type it is given, button by default, and as an anchor a link to its href opening in a new tab only when asked', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:TabsItem', content: 'Orders')->crawler())
        ->toContainElement('button' . SupportSelectors::STANDALONE_ROOT . '.sy-tabs-item[type="button"]:not([href]):not([target]) .sy-tabs-item-label', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['type' => 'submit'], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item[type="submit"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['tag' => 'a', 'href' => '/orders', 'target' => '_blank'], content: 'Orders')->crawler())
        ->toContainElement('a.sy-tabs-item[href="/orders"][target="_blank"]:not([type])', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['tag' => 'a', 'href' => '/orders', 'target' => '_self'], content: 'Orders')->crawler())
        ->toContainElement('a.sy-tabs-item[href="/orders"]:not([target])', 'Orders');
});

it('still opens in a new tab when asked as an anchor with no href', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:TabsItem', ['tag' => 'a', 'target' => '_blank'], content: 'Orders')->crawler())
        ->toContainElement('a.sy-tabs-item[target="_blank"]:not([href])', 'Orders');
});

it('marks itself the current tab while active, and the current page as an anchor, and neither while inactive', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:TabsItem', ['active' => true], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item' . SupportSelectors::ACTIVE . '[aria-current="true"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['tag' => 'a', 'href' => '/orders', 'active' => true], content: 'Orders')->crawler())
        ->toContainElement('a.sy-tabs-item' . SupportSelectors::ACTIVE . '[aria-current="page"]', 'Orders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item:not(' . SupportSelectors::ACTIVE . '):not([aria-current])', 'Orders');
});

it('draws its icon before its label, after it when positioned after, and the icon an application registers under its alias', function (): void {
    $this::getContainer()->get(IconManager::class)->register(['tests.orders-tab' => 'support:heart']);

    expect($this->renderTwigComponent('SylamentSupport:TabsItem', ['icon' => 'support:star'], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item', 'StarOrders')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['icon' => 'support:star', 'iconPosition' => 'after'], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item', 'OrdersStar')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['icon' => 'support:star', 'iconPosition' => IconPosition::After], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item', 'OrdersStar')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['icon' => 'support:star', 'iconAlias' => 'tests.orders-tab'], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item', 'HeartOrders');
});

it('shows a scalar badge as a small badge, primary unless coloured, with its icon and its tooltip as a title, and no badge without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:TabsItem', ['badge' => 3], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item ' . SupportSelectors::BADGE . SupportSelectors::size('sm') . SupportSelectors::color('primary') . ':not([title])', '3')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['badge' => 3, 'badgeColor' => 'danger', 'badgeIcon' => 'support:star', 'badgeIconPosition' => IconPosition::After, 'badgeTooltip' => 'Three new orders'], content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item ' . SupportSelectors::BADGE . SupportSelectors::color('danger') . '[title="Three new orders"]', '3Star')
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', content: 'Orders')->crawler())
        ->toContainElement('button.sy-tabs-item', 'Orders')
        ->not->toContainElement(SupportSelectors::BADGE);
});

it('shows a badge block as it is, over a scalar badge, unless the block is blank', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:TabsItem', ['badge' => 3], content: 'Orders', blocks: ['badge' => '<span data-deferred-badge>7</span>'])->crawler())
        ->toContainElement('button.sy-tabs-item [data-deferred-badge]', '7')
        ->not->toContainElement(SupportSelectors::BADGE)
        ->and($this->renderTwigComponent('SylamentSupport:TabsItem', ['badge' => 3], content: 'Orders', blocks: ['badge' => '  '])->crawler())
        ->toContainElement('button.sy-tabs-item ' . SupportSelectors::BADGE, '3');
});

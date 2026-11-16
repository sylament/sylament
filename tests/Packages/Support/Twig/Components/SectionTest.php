<?php

declare(strict_types=1);

use Sylament\Support\Colors\Color;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Twig\Markup;

it('shows a header with its heading in the tag it is given, its description and its after-header block, and no header with nothing to put in it', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'description' => 'Every order placed.'], blocks: ['afterHeader' => '<button type="button">Export</button>'])->crawler())
        ->toContainElement('section.sy.sy-section header.sy-section-header h2', 'Orders')
        ->toContainElement('section.sy-section header p', 'Every order placed.')
        ->toContainElement('section.sy-section header .sy-section-header-after-ctn button', 'Export')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'headingTag' => 'h3'])->crawler())
        ->toContainElement('section.sy-section header h3', 'Orders')
        ->not->toContainElement('h2')
        ->and($this->renderTwigComponent('SylamentSupport:Section', content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section .sy-section-content', 'Ten orders.')
        ->not->toContainElement('header');
});

it('shows its content and its footer in a body, the content unwrapped when told, and no body without either', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', content: 'Ten orders.', blocks: ['footer' => '<button type="button">Load more</button>'])->crawler())
        ->toContainElement('section.sy-section .sy-section-content-ctn .sy-section-content', 'Ten orders.')
        ->toContainElement('section.sy-section .sy-section-content-ctn footer.sy-section-footer button', 'Load more')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['hasContentEl' => false], content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section .sy-section-content-ctn', 'Ten orders.')
        ->not->toContainElement('.sy-section-content')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders'])->crawler())
        ->toContainElement('section.sy-section h2', 'Orders')
        ->not->toContainElement('.sy-section-content-ctn');
});

it('shows a heading and a description given as text as text, and given as markup as markup', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => '<em>Orders</em>', 'description' => '<em>Every order.</em>'])->crawler())
        ->toContainElement('h2', '<em>Orders</em>')
        ->toContainElement('p', '<em>Every order.</em>')
        ->not->toContainElement('em')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => new Markup('<em>Orders</em>', 'UTF-8'), 'description' => new Markup('<em>Every order.</em>', 'UTF-8')])->crawler())
        ->toContainElement('h2 em', 'Orders')
        ->toContainElement('p em', 'Every order.');
});

it('draws its icon in the header, large and gray unless sized or coloured, and a shade map inline', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['icon' => 'support:star'])->crawler())
        ->toContainElement('header ' . SupportSelectors::ICON . SupportSelectors::size('lg') . SupportSelectors::GRAY . ':not([style])', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['icon' => 'support:star', 'iconSize' => 'sm', 'iconColor' => 'danger'])->crawler())
        ->toContainElement('header ' . SupportSelectors::ICON . SupportSelectors::size('sm') . SupportSelectors::color('danger'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['icon' => 'support:star', 'iconColor' => Color::Rose])->crawler())
        ->toContainElement('header ' . SupportSelectors::ICON . SupportSelectors::COLORED . '[style*="--sy-color-500: ' . Color::Rose[500] . ';"]', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders'])->crawler())
        ->toContainElement('header h2', 'Orders')
        ->not->toContainElement('svg');
});

it('takes its variant classes from its props', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'contained' => false, 'contentBefore' => true, 'aside' => true, 'compact' => true, 'divided' => true, 'secondary' => true])->crawler())
        ->toContainElement('section.sy-section' . SupportSelectors::NOT_CONTAINED . '.sy-section-has-header.sy-section-has-content-before.sy-aside.sy-compact.sy-divided.sy-secondary')
        ->and($this->renderTwigComponent('SylamentSupport:Section', content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section:not(' . SupportSelectors::NOT_CONTAINED . '):not(.sy-section-has-header):not(.sy-section-has-content-before):not(.sy-aside):not(.sy-compact):not(.sy-divided):not(.sy-secondary):not(.sy-collapsible)', 'Ten orders.');
});

it('is a disclosure toggled from its header and from a chevron button controlling its body, and none of it unless collapsible', function (): void {
    $section = '[data-controller="sylament--support--section"]'
        . '[data-sylament--support--section-id-value="orders"]'
        . '[data-sylament--support--section-collapsed-value="false"]'
        . ':not([data-sylament--support--section-persist-value])'
        . '[data-action~="collapse-section@window->sylament--support--section#collapse"]'
        . '[data-action~="expand-section@window->sylament--support--section#expand"]'
        . '[data-action~="open-section@window->sylament--support--section#expand"]'
        . '[data-action~="toggle-section@window->sylament--support--section#toggle"]'
        . '[data-action~="expand->sylament--support--section#reveal"]';
    $button = 'button.sy-section-collapse-btn[data-action="click->sylament--support--section#toggleFromButton"][data-sylament--support--section-target="button"]';

    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'collapseId' => 'orders', 'contentId' => 'orders-content'], content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section.sy-collapsible' . $section . ' header[data-action="click->sylament--support--section#toggleFromHeader"] h2', 'Orders')
        ->toContainElement('header ' . $button . '[aria-expanded="true"][aria-controls="orders-content"] svg', 'chevron-up-20-solid')
        ->toContainElement('.sy-section-content-ctn#orders-content[data-sylament--support--section-target="content"]', 'Ten orders.')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'persistCollapsed' => true, 'contentId' => 'orders-content'])->crawler())
        ->toContainElement('section.sy-section[data-sylament--support--section-persist-value="true"]:not([data-sylament--support--section-id-value])')
        ->toContainElement($button . '[aria-expanded="true"]:not([aria-controls])', 'chevron-up-20-solid')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'contentId' => 'orders-content'], content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section:not(.sy-collapsible):not([data-controller]):not([data-action]) header:not([data-action]) h2', 'Orders')
        ->toContainElement('.sy-section-content-ctn#orders-content:not([data-sylament--support--section-target])', 'Ten orders.')
        ->not->toContainElement('button');
});

it('is collapsed on first paint only when collapsible', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'collapsed' => true])->crawler())
        ->toContainElement('section.sy-section.sy-collapsed[data-sylament--support--section-collapsed-value="true"]')
        ->toContainElement('button.sy-section-collapse-btn[aria-expanded="false"]')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsed' => true])->crawler())
        ->toContainElement('section.sy-section:not(.sy-collapsed) h2', 'Orders');
});

it('hides its body until its controller connects only when it restores a stored collapsed state', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['collapsible' => true, 'persistCollapsed' => true], content: 'Ten orders.')->crawler())
        ->toContainElement('.sy-section-content-ctn.sy-cloak', 'Ten orders.')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['collapsible' => true, 'collapsed' => true], content: 'Ten orders.')->crawler())
        ->toContainElement('.sy-section-content-ctn:not(.sy-cloak)', 'Ten orders.')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['persistCollapsed' => true], content: 'Ten orders.')->crawler())
        ->toContainElement('.sy-section-content-ctn:not(.sy-cloak)', 'Ten orders.');
});

it('is labelled by its heading and described by its description through the ids it is given, and by neither without them', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'description' => 'Every order placed.', 'headingId' => 'orders-heading', 'descriptionId' => 'orders-description', 'labelledById' => 'orders-heading', 'contentId' => 'orders-content'], content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section[aria-labelledby="orders-heading"][aria-describedby="orders-description"]')
        ->toContainElement('h2#orders-heading', 'Orders')
        ->toContainElement('p#orders-description', 'Every order placed.')
        ->toContainElement('.sy-section-content-ctn#orders-content', 'Ten orders.')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'description' => 'Every order placed.'], content: 'Ten orders.')->crawler())
        ->toContainElement('section.sy-section:not([aria-labelledby]):not([aria-describedby])')
        ->toContainElement('h2:not([id])', 'Orders')
        ->toContainElement('p:not([id])', 'Every order placed.')
        ->toContainElement('.sy-section-content-ctn:not([id])', 'Ten orders.');
});

it('names its collapse button Collapse section while expanded and Expand section while collapsed, unless named otherwise', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true])->crawler())
        ->toContainButtons('button.sy-section-collapse-btn', ['Collapse section'])
        ->toContainElement('section.sy-section[data-sylament--support--section-expand-label-value="Expand section"][data-sylament--support--section-collapse-label-value="Collapse section"]')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'collapsed' => true])->crawler())
        ->toContainButtons('button.sy-section-collapse-btn', ['Expand section'])
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'collapseLabel' => 'Hide orders', 'expandLabel' => 'Show orders'])->crawler())
        ->toContainButtons('button.sy-section-collapse-btn', ['Hide orders'])
        ->toContainElement('section.sy-section[data-sylament--support--section-expand-label-value="Show orders"][data-sylament--support--section-collapse-label-value="Hide orders"]')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'collapsed' => true, 'collapseLabel' => 'Hide orders', 'expandLabel' => 'Show orders'])->crawler())
        ->toContainButtons('button.sy-section-collapse-btn', ['Show orders']);
});

it('resets its collapsed state with the live component it belongs to, unless it persists the state or either id is missing', function (): void {
    $reset = '[data-action~="reset-schema-component-state@window->sylament--support--section#resetState"]';

    expect($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'liveComponentId' => 'orders-page', 'schemaKey' => 'form.orders'])->crawler())
        ->toContainElement('section.sy-section' . $reset . '[data-sylament--support--section-live-component-id-value="orders-page"][data-sylament--support--section-schema-key-value="form.orders"]')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'liveComponentId' => 'orders-page'])->crawler())
        ->toContainElement('section.sy-section[data-controller="sylament--support--section"]:not(' . $reset . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'schemaKey' => 'form.orders'])->crawler())
        ->toContainElement('section.sy-section[data-controller="sylament--support--section"]:not(' . $reset . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Section', ['heading' => 'Orders', 'collapsible' => true, 'persistCollapsed' => true, 'liveComponentId' => 'orders-page', 'schemaKey' => 'form.orders'])->crawler())
        ->toContainElement('section.sy-section[data-controller="sylament--support--section"]:not(' . $reset . ')');
});

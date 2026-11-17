<?php

declare(strict_types=1);

use Sylament\Schemas\Components\Html;
use Sylament\Support\Colors\Color;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\SlideOverPosition;
use Sylament\Support\Enums\Width;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;

it('is a dialog around one window, wired to its controller and to the open and close events naming its id', function (): void {
    $modal = $this->renderTwigComponent('SylamentSupport:Modal', ['id' => 'delete-order', 'class' => 'danger-zone'])->crawler();
    $dialog = 'dialog#delete-order' . SupportSelectors::STANDALONE_ROOT . '.sy-modal.sy-fixed-positioning-context.danger-zone'
        . '[data-controller="sylament--support--modal"]'
        . SupportSelectors::modalValue('id', 'delete-order')
        . '[data-action~="sylament-open-modal@window->sylament--support--modal#open"]'
        . '[data-action~="sylament-close-modal@window->sylament--support--modal#close"]'
        . '[data-action~="cancel->sylament--support--modal#handleCancel"]'
        . '[data-action~="pointerdown->sylament--support--modal#trackPointerDown"]'
        . '[data-action~="click->sylament--support--modal#closeOnClickAway"]'
        . '[data-action~="close->sylament--support--modal#notifyDismissed"]'
        . '[data-action~="close->sylament--support--modal#restoreFocus"]';

    expect($modal)->toContainElement($dialog)
        ->and($modal->filter($dialog . ' ' . SupportSelectors::MODAL_WINDOW))->toHaveCount(1);
});

it('closes on escape and on a click away unless told not to', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::MODAL_CLICKABLE . ':not(' . SupportSelectors::modalValue('close-by-escaping') . '):not(' . SupportSelectors::modalValue('close-by-clicking-away') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['closeByEscaping' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::MODAL_CLICKABLE . SupportSelectors::modalValue('close-by-escaping', 'false'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['closeByClickingAway' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not(' . SupportSelectors::MODAL_CLICKABLE . ')' . SupportSelectors::modalValue('close-by-clicking-away', 'false') . ':not(' . SupportSelectors::modalValue('close-by-escaping') . ')');
});

it('renders open, natively and as its open value, only when open', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['open' => true])->crawler())
        ->toContainElement(SupportSelectors::OPEN_MODAL . SupportSelectors::modalValue('open', 'true'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not([open]):not(' . SupportSelectors::modalValue('open') . ')');
});

it('marks a modal opened over another as overlaid', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['overlaid' => true])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::MODAL_OVERLAID)
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not(' . SupportSelectors::MODAL_OVERLAID . ')');
});

it('is labelled by its heading and described by its description when it has an id, and by neither without them', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['id' => 'delete-order', 'heading' => 'Delete order', 'description' => 'This cannot be undone.'])->crawler())
        ->toContainElement('dialog[aria-labelledby="delete-order-heading"][aria-describedby="delete-order-description"]')
        ->toContainElement('h2#delete-order-heading', 'Delete order')
        ->toContainElement('p#delete-order-description', 'This cannot be undone.')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['id' => 'delete-order', 'heading' => 'Delete order'])->crawler())
        ->toContainElement('dialog[aria-labelledby="delete-order-heading"]:not([aria-describedby])')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['id' => 'delete-order', 'description' => 'This cannot be undone.'])->crawler())
        ->toContainElement('dialog#delete-order:not([aria-labelledby]):not([aria-describedby])')
        ->toContainElement('p.sy-modal-description:not([id])', 'This cannot be undone.')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order'])->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not([aria-labelledby])')
        ->toContainElement('h2:not([id])', 'Delete order');
});

it('shows a header with a close button for an icon or a description alone, and none without either or a heading', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star'])->crawler())
        ->toContainElement('header.sy-modal-header .sy-modal-icon-ctn', 'Star')
        ->toContainElement('header.sy-modal-header ' . SupportSelectors::MODAL_CLOSE_BUTTON)
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['description' => 'This cannot be undone.'])->crawler())
        ->toContainElement('header.sy-modal-header p', 'This cannot be undone.')
        ->toContainElement('header.sy-modal-header ' . SupportSelectors::MODAL_CLOSE_BUTTON)
        ->and($this->renderTwigComponent('SylamentSupport:Modal', content: 'Are you sure?')->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW, 'Are you sure?')
        ->not->toContainElement('header')
        ->not->toContainElement(SupportSelectors::MODAL_CLOSE_BUTTON);
});

it('centres a heading beside an icon vertically when nothing describes it and it is aligned to the start', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order', 'icon' => 'support:star'])->crawler())
        ->toContainElement('header.sy-modal-header' . SupportSelectors::verticalAlign('center') . ' h2', 'Delete order')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order', 'icon' => 'support:star', 'description' => 'This cannot be undone.'])->crawler())
        ->toContainElement('header.sy-modal-header:not(' . SupportSelectors::verticalAlign('center') . ') h2', 'Delete order')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order', 'icon' => 'support:star', 'alignment' => Alignment::Center])->crawler())
        ->toContainElement('header.sy-modal-header:not(' . SupportSelectors::verticalAlign('center') . ') h2', 'Delete order');
});

it('paints its icon in primary unless coloured, gray and an empty colour uncoloured, and a shade map inline', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star'])->crawler())
        ->toContainElement('.sy-modal-icon-ctn ' . SupportSelectors::ICON . SupportSelectors::COLORED . SupportSelectors::color('primary'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star', 'iconColor' => 'danger'])->crawler())
        ->toContainElement('.sy-modal-icon-ctn ' . SupportSelectors::ICON . SupportSelectors::COLORED . SupportSelectors::color('danger'), 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star', 'iconColor' => 'gray'])->crawler())
        ->toContainElement('.sy-modal-icon-ctn ' . SupportSelectors::ICON . SupportSelectors::GRAY . ':not([style])', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star', 'iconColor' => ''])->crawler())
        ->toContainElement('.sy-modal-icon-ctn ' . SupportSelectors::ICON . SupportSelectors::GRAY . ':not([style])', 'Star')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['icon' => 'support:star', 'iconColor' => Color::Rose])->crawler())
        ->toContainElement('.sy-modal-icon-ctn ' . SupportSelectors::ICON . SupportSelectors::COLORED . SupportSelectors::palette(Color::Rose[500]), 'Star');
});

it('shows its content in a content region, and no region without content', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', content: 'Are you sure?')->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '.sy-modal-window-has-content .sy-modal-content', 'Are you sure?')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', blocks: ['footer' => '<button type="button">Confirm</button>'])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . ':not(.sy-modal-window-has-content) footer button', 'Confirm')
        ->not->toContainElement('.sy-modal-content');
});

it('shows its footer block in a footer, and no footer without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', blocks: ['footer' => '<button type="button">Confirm</button>'])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '.sy-modal-window-has-footer footer' . SupportSelectors::MODAL_FOOTER . ' button', 'Confirm')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', content: 'Are you sure?')->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . ':not(.sy-modal-window-has-footer)', 'Are you sure?')
        ->not->toContainElement('footer');
});

it('lays its footer actions out in the footer unless a footer block replaces them, aligned when asked', function (): void {
    $actions = [Html::make('<button type="button">Confirm</button>'), Html::make('<button type="button">Cancel</button>')];

    expect($this->renderTwigComponent('SylamentSupport:Modal', ['footerActions' => $actions])->crawler())
        ->toContainButtons(SupportSelectors::MODAL_WINDOW . '.sy-modal-window-has-footer footer' . SupportSelectors::MODAL_FOOTER . SupportSelectors::NO_ALIGN . ' .sy-modal-footer-actions button', ['Confirm', 'Cancel'])
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['footerActions' => $actions], blocks: ['footer' => '<button type="button">Done</button>'])->crawler())
        ->toContainButtons('footer' . SupportSelectors::MODAL_FOOTER . ' button', ['Done'])
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['footerActions' => $actions, 'footerActionsAlignment' => Alignment::Center])->crawler())
        ->toContainButtons('footer' . SupportSelectors::MODAL_FOOTER . SupportSelectors::align('center') . ' .sy-modal-footer-actions button', ['Confirm', 'Cancel'])
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['footerActions' => $actions, 'footerActionsAlignment' => 'spread'])->crawler())
        ->toContainButtons('footer' . SupportSelectors::MODAL_FOOTER . '.spread' . SupportSelectors::NO_ALIGN . ' .sy-modal-footer-actions button', ['Confirm', 'Cancel']);
});

it('is medium wide unless sized, the screen width also stretching the dialog', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not(' . SupportSelectors::modalWidth('screen') . ') ' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['width' => Width::Large])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('lg') . ':not(' . SupportSelectors::modalWidth('md') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['width' => Width::Screen])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalWidth('screen') . ' ' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('screen'));
});

it('returns focus to its opener unless told not to', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['returnsFocus' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalValue('returns-focus', 'false'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not(' . SupportSelectors::modalValue('returns-focus') . ')');
});

it('closes from a button named Close in its header, kept out of the tab order while escape closes, and focuses its window when asked', function (): void {
    $closing = SupportSelectors::MODAL_CLOSE_BUTTON . SupportSelectors::MODAL_DISMISS;

    expect($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order'])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '.sy-modal-window-has-close-btn:not([autofocus]):not([tabindex]) header ' . $closing . '[tabindex="-1"]')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order', 'closeByEscaping' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '[autofocus][tabindex="-1"] header ' . $closing . ':not([tabindex])')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['heading' => 'Delete order', 'closeButton' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . ':not(.sy-modal-window-has-close-btn) h2', 'Delete order')
        ->not->toContainElement(SupportSelectors::MODAL_CLOSE_BUTTON)
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['autofocus' => false])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '[autofocus][tabindex="-1"]');
});

it('slides over from the end unless told to slide from the start', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['slideOver' => true])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalSlideOver() . SupportSelectors::modalSlideOver('end') . ':not(' . SupportSelectors::modalSlideOver('start') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['slideOver' => true, 'slideOverPosition' => SlideOverPosition::Start])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalSlideOver() . SupportSelectors::modalSlideOver('start') . ':not(' . SupportSelectors::modalSlideOver('end') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not([class*="sy-modal-slide-over"])');
});

it('sticks its header and its footer each on its own', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['stickyHeader' => true])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalSticky('header') . ':not(' . SupportSelectors::modalSticky('footer') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['stickyFooter' => true])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::modalSticky('footer') . ':not(' . SupportSelectors::modalSticky('header') . ')');
});

it('aligns its window to the start unless aligned, and an alignment outside the known ones as its own class', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . SupportSelectors::align('start'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['alignment' => Alignment::Center])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . SupportSelectors::align('center') . ':not(' . SupportSelectors::align('start') . ')')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['alignment' => 'spread'])->crawler())
        ->toContainElement(SupportSelectors::MODAL_WINDOW . '.spread' . SupportSelectors::NO_ALIGN);
});

it('lets clicks through to the page, never closing on a click away', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['clickThrough' => true, 'closeByClickingAway' => true])->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::MODAL_CLICK_THROUGH . ':not(' . SupportSelectors::MODAL_CLICKABLE . ')' . SupportSelectors::modalValue('click-through', 'true') . SupportSelectors::modalValue('close-by-clicking-away', 'false'))
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . SupportSelectors::MODAL_CLICKABLE . ':not(' . SupportSelectors::MODAL_CLICK_THROUGH . '):not(' . SupportSelectors::modalValue('click-through') . ')');
});

it('puts its extra window attributes on the window and its extra overlay attributes on the dialog, appending classes and styles', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', [
        'class' => 'danger-zone',
        'style' => 'color: red',
        'extraOverlayAttributes' => ['class' => 'dimmed', 'style' => 'top: 0', 'data-overlay' => 'order'],
        'extraWindowAttributes' => ['class' => 'roomy', 'data-window' => 'order'],
    ])->crawler())
        ->toContainElement(SupportSelectors::MODAL . '.danger-zone.dimmed[style^="top: 0"][style$="color: red"][data-overlay="order"]:not([data-window]) ' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md') . '.roomy[data-window="order"]:not([data-overlay])')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['extraOverlayAttributes' => ['style' => 'top: 0']])->crawler())
        ->toContainElement(SupportSelectors::MODAL . '[style="top: 0"]')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', ['style' => 'color: red'])->crawler())
        ->toContainElement(SupportSelectors::MODAL . '[style="color: red"]')
        ->and($this->renderTwigComponent('SylamentSupport:Modal')->crawler())
        ->toContainElement(SupportSelectors::MODAL . ':not([style])');
});

it('submits its window as a form to the live action it names, and is no form without one', function (): void {
    expect($this->renderTwigComponent('SylamentSupport:Modal', ['submitHandler' => 'callMountedAction'], content: 'Are you sure?')->crawler())
        ->toContainElement('form' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md') . '[data-action="submit->live#action:prevent"][data-live-action-param="callMountedAction"]', 'Are you sure?')
        ->and($this->renderTwigComponent('SylamentSupport:Modal', content: 'Are you sure?')->crawler())
        ->toContainElement('div' . SupportSelectors::MODAL_WINDOW . SupportSelectors::modalWidth('md') . ':not([data-action])', 'Are you sure?')
        ->not->toContainElement('form');
});

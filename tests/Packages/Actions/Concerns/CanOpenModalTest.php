<?php

declare(strict_types=1);

use Sylament\Actions\Action;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\SlideOverPosition;
use Sylament\Tests\Fixtures\Actions\ModalChromeHost;
use Sylament\Tests\Fixtures\Actions\Selectors;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Markup;

beforeEach(function (): void {
    $this->modal = fn (Action $action): Crawler => $this->renderTwigComponent('SylamentActions:ActionModal', ['action' => $action, 'id' => 'archive-modal'])->crawler();
});

describe('presence', function (): void {
    it('opens a modal on mount for a heading, a description, content, a content footer or modal(), running nothing yet', function (string $name): void {
        $tester = $this->createLiveComponent(ModalChromeHost::class)->mountAction($name)->assertActionMounted($name);

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->count())->toBe(1)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    })->with([
        'a heading' => ['headed'],
        'a description' => ['described'],
        'content' => ['contented'],
        'a content footer' => ['footed'],
        'modal()' => ['forced'],
    ]);

    it('runs a hidden modal at once whatever it has to show, unless modal(true) insists on opening it', function (): void {
        $this->createLiveComponent(ModalChromeHost::class)->mountAction('hushed')->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['hushed']);

        $tester = $this->createLiveComponent(ModalChromeHost::class)->mountAction('insisted')->assertActionMounted('insisted');

        expect($tester->render()->crawler()->filter(Selectors::mountedModal(0) . Selectors::OPEN)->count())->toBe(1)
            ->and($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);
    });
});

describe('chrome', function (): void {
    it('heads its modal with its heading, or its label without one, over its description and its icon in its icon colour or its own', function (): void {
        expect(($this->modal)(Action::make('archive')->modalHeading('Archive the order')->modalDescription('It leaves the list.')->modalIcon('support:star')->modalIconColor('danger')))
            ->toContainElement('h2', 'Archive the order')
            ->toContainElement('p', 'It leaves the list.')
            ->toContainElement('header svg' . SupportSelectors::color('danger'), 'Star')
            ->and(($this->modal)(Action::make('archive')->color('warning')->modalIcon('support:star')))
            ->toContainElement('h2', 'Archive')
            ->toContainElement('header svg' . SupportSelectors::color('warning'), 'Star');
    });

    it('renders the content and the content footer it is given', function (): void {
        $content = static fn (string $text): Markup => new Markup("<p>{$text}</p>", 'UTF-8');

        expect(($this->modal)(Action::make('archive')->modalContent($content('Three orders move.'))->modalContentFooter($content('Nothing is deleted.'))))
            ->toContainElement('p', 'Three orders move.')
            ->toContainElement('p', 'Nothing is deleted.');
    });

    it('opens at 4xl aligned to its start, closable by its button, a click away or ESC, its first field taking the focus, with Submit before Cancel', function (): void {
        $modal = ($this->modal)(Action::make('archive'));

        expect($modal)->toContainElement(Selectors::MODAL_WINDOW . '.sy-modal-4xl' . SupportSelectors::align('start') . ':not([autofocus])')
            ->toContainElement('dialog.sy-clickable:not([data-sylament--support--modal-close-by-escaping-value]):not([data-sylament--support--modal-close-by-clicking-away-value])')
            ->toContainElement(SupportSelectors::MODAL_CLOSE_BUTTON)
            ->toContainButtons(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON, ['Submit', 'Cancel']);
    });

    it('lays its modal out at the width, alignment and side it is given, a narrow one centred and a slide-over sticking its header and footer unless told otherwise', function (): void {
        expect(($this->modal)(Action::make('archive')->modalWidth('2xl')->modalAlignment(Alignment::Center)))
            ->toContainElement(Selectors::MODAL_WINDOW . '.sy-modal-2xl' . SupportSelectors::align('center'))
            ->and(($this->modal)(Action::make('archive')->modalWidth('sm')))
            ->toContainElement(Selectors::MODAL_WINDOW . '.sy-modal-sm' . SupportSelectors::align('center'))
            ->and(($this->modal)(Action::make('archive')->slideOver()->slideOverPosition(SlideOverPosition::Start)))
            ->toContainElement('dialog.sy-modal-slide-over-from-start.sy-modal-has-sticky-header.sy-modal-has-sticky-footer')
            ->and(($this->modal)(Action::make('archive')->slideOver()->stickyModalFooter(false)))
            ->toContainElement('dialog.sy-modal-slide-over-from-end.sy-modal-has-sticky-header:not(.sy-modal-has-sticky-footer)')
            ->and(($this->modal)(Action::make('archive')->stickyModalHeader()))
            ->toContainElement('dialog.sy-modal-has-sticky-header:not(.sy-modal-slide-over):not(.sy-modal-has-sticky-footer)');
    });

    it('drops its close button, its click-away and ESC dismissal and its autofocus when told, and lets clicks through to the page', function (): void {
        $modal = ($this->modal)(Action::make('archive')->modalCloseButton(false)->closeModalByClickingAway(false)->closeModalByEscaping(false)->modalAutofocus(false));

        expect($modal)->toContainElement('dialog[data-sylament--support--modal-close-by-escaping-value="false"][data-sylament--support--modal-close-by-clicking-away-value="false"]:not(.sy-clickable)')
            ->toContainElement(Selectors::MODAL_WINDOW . '[autofocus][tabindex="-1"]')
            ->and($modal->filter(SupportSelectors::MODAL_CLOSE_BUTTON)->count())->toBe(0)
            ->and(($this->modal)(Action::make('archive')->modalClickThrough()))
            ->toContainElement('dialog.sy-modal-click-through[data-sylament--support--modal-click-through-value="true"]:not(.sy-clickable)');
    });
});

describe('a confirmation', function (): void {
    it('asks whether to go on in a medium modal centred under a warning icon, Cancel before Confirm in a centred footer', function (): void {
        $modal = ($this->modal)(Action::make('archive')->requiresConfirmation());

        expect($modal)->toContainElement(Selectors::MODAL_WINDOW . '.sy-modal-md' . SupportSelectors::align('center'))
            ->toContainElement('p', 'Are you sure you would like to do this?')
            ->toContainElement(Selectors::MODAL_FOOTER . SupportSelectors::align('center'))
            ->toContainButtons(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON, ['Cancel', 'Confirm'])
            ->and($modal->filter('header svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['x-mark', ''])
            ->and(($this->modal)(Action::make('archive'))->filter('header svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['x-mark']);
    });

    it('gives way to the description, icon, submit label, width and alignments it is given', function (): void {
        $modal = ($this->modal)(Action::make('archive')->requiresConfirmation()
            ->modalDescription('It leaves the list.')
            ->modalIcon('support:star')
            ->modalSubmitActionLabel('Archive')
            ->modalWidth('2xl')
            ->modalAlignment(Alignment::Start)
            ->modalFooterActionsAlignment(Alignment::End));

        expect($modal)->toContainElement(Selectors::MODAL_WINDOW . '.sy-modal-2xl' . SupportSelectors::align('start'))
            ->toContainElement('p', 'It leaves the list.')
            ->toContainElement(Selectors::MODAL_FOOTER . SupportSelectors::align('end'))
            ->toContainButtons(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON, ['Archive', 'Cancel'])
            ->and($modal->filter('header svg')->each(static fn (Crawler $icon): string => $icon->text()))->toBe(['x-mark', 'Star'])
            ->and($modal->filter('p')->each(static fn (Crawler $paragraph): string => $paragraph->text()))->not->toContain('Are you sure you would like to do this?');
    });
});

describe('footer', function (): void {
    it('renders Submit, then its extra footer actions, then Cancel, under the labels it is given, reversed when centred', function (): void {
        $action = static fn (): Action => Action::make('archive')
            ->extraModalFooterActions([Action::make('saveDraft')])
            ->modalSubmitActionLabel('Archive')
            ->modalCancelActionLabel('Keep it');

        expect(($this->modal)($action()))
            ->toContainButtons(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON, ['Archive', 'Save draft', 'Keep it'])
            ->and(($this->modal)($action()->modalFooterActionsAlignment(Alignment::Center)))
            ->toContainButtons(Selectors::MODAL_FOOTER . SupportSelectors::align('center') . ' ' . SupportSelectors::BUTTON, ['Keep it', 'Save draft', 'Archive']);
    });

    it('leaves out a Submit or Cancel set to false, renders the one a closure makes of it, and shows only the visible actions that replace the row', function (): void {
        $buttons = fn (Action $action): array => ($this->modal)($action)->filter(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON)->each(static fn (Crawler $button): string => $button->text());

        expect($buttons(Action::make('archive')->modalSubmitAction(false)))->toBe(['Cancel'])
            ->and($buttons(Action::make('archive')->modalCancelAction(false)))->toBe(['Submit'])
            ->and($buttons(Action::make('archive')->modalSubmitAction(fn (Action $action): Action => $action->label('Send'))))->toBe(['Send', 'Cancel'])
            ->and($buttons(Action::make('archive')->extraModalFooterActions([Action::make('saveDraft')])->modalFooterActions([Action::make('print'), Action::make('email')->hidden()])))->toBe(['Print']);
    });

    it('submits the form from its Submit and dismisses the modal in the browser from its Cancel', function (): void {
        expect(($this->modal)(Action::make('archive')))
            ->toContainElement('form' . Selectors::MODAL_WINDOW . '[data-live-action-param="callMountedAction"] ' . Selectors::MODAL_FOOTER . ' button[type="submit"]', 'Submit')
            ->toContainElement(Selectors::MODAL_FOOTER . ' button[type="button"][data-action="sylament--support--modal#dismiss"]', 'Cancel');
    });

    it("paints Submit primary for a gray or uncoloured action and in the action's colour otherwise", function (?string $color, string $submit): void {
        expect(($this->modal)(Action::make('archive')->color($color)))
            ->toContainElement(Selectors::MODAL_FOOTER . ' button[type="submit"]' . SupportSelectors::color($submit), 'Submit');
    })->with([
        'gray' => ['gray', 'primary'],
        'uncoloured' => [null, 'primary'],
        'another colour' => ['danger', 'danger'],
    ]);

    it('hands a footer action the record of its parent, unless it has one of its own', function (): void {
        $this->seed($lamp = new Product('Lamp'), $desk = new Product('Desk'));
        $label = static fn (Product $record): string => "Stamp {$record->name}";

        expect(($this->modal)(Action::make('archive')->record($lamp)->extraModalFooterActions([
            Action::make('stamp')->label($label),
            Action::make('stampOwn')->record($desk)->label($label),
        ])))->toContainButtons(Selectors::MODAL_FOOTER . ' ' . SupportSelectors::BUTTON, ['Submit', 'Stamp Lamp', 'Stamp Desk', 'Cancel']);
    });
});

describe('extra footer actions', function (): void {
    it('runs a footer action, or one in a footer group, under the open modal, and the parent again from a create-another action', function (): void {
        $tester = $this->createLiveComponent(ModalChromeHost::class)->mountAction('compose');
        $footer = $tester->render()->crawler()->filter(Selectors::mountedModal(0) . ' ' . Selectors::MODAL_FOOTER);

        expect($footer)->toContainButtons(SupportSelectors::BUTTON, ['Submit', 'Compose another', 'Save draft', 'Cancel'])
            ->toContainElement(SupportSelectors::BUTTON . '[data-live-action-param="callMountedAction"][data-live-arguments-param=\'{"another":true}\']', 'Compose another')
            ->toContainElement('[data-controller="sylament--support--dropdown"] button[data-live-action-param="mountAction"][data-live-name-param="share"]', 'Share');

        $tester->mountAction('saveDraft')->assertActionMounted('compose');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['saveDraft']);

        $tester->mountAction('share')->assertActionMounted('compose');

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['share']);

        $tester->callMountedAction(['another' => true])->assertActionNotMounted();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['compose: {"another":true}']);
    });
});

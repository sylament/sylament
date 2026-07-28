<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\Tests\Fixtures\Concerns\BothContractsHostFixture;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\SlideOverPosition;
use Twig\Markup;

it('defaults the modal heading to the label', function (): void {
    expect(Action::make('delete')->getModalHeading())->toBe('Delete');
});

it('overrides the modal heading and description', function (): void {
    $action = Action::make('delete')->modalHeading('Delete?')->modalDescription('This cannot be undone.');

    expect($action->getModalHeading())->toBe('Delete?')
        ->and($action->getModalDescription())->toBe('This cannot be undone.');
});

it('has a null modal description by default', function (): void {
    expect(Action::make('delete')->getModalDescription())->toBeNull();
});

it('defaults the confirm icon only when confirmation is required', function (): void {
    expect(Action::make('delete')->getModalIcon())->toBeNull()
        ->and(Action::make('delete')->requiresConfirmation()->getModalIcon())->toBe('heroicons:exclamation-triangle');
});

it('derives the modal icon color from the action color, falling back to primary', function (): void {
    expect(Action::make('delete')->getModalIconColor())->toBe('primary')
        ->and(Action::make('delete')->color('danger')->getModalIconColor())->toBe('danger');
});

it('defaults the submit label to Confirm when confirming, else Submit', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->getModalSubmitActionLabel())->toBe('Confirm')
        ->and(Action::make('delete')->getModalSubmitActionLabel())->toBe('Submit')
        ->and(Action::make('delete')->modalSubmitActionLabel('Yes')->getModalSubmitActionLabel())->toBe('Yes');
});

it('defaults the cancel label and modal width', function (): void {
    expect(Action::make('delete')->getModalCancelActionLabel())->toBe('Cancel')
        ->and(Action::make('delete')->getModalWidth())->toBe('md')
        ->and(Action::make('delete')->modalWidth('lg')->getModalWidth())->toBe('lg');
});

it('does not open a modal for a bare action', function (): void {
    expect(Action::make('delete')->shouldOpenModal())->toBeFalse();
});

it('forces the modal open or closed through modal()', function (): void {
    expect(Action::make('delete')->modal()->shouldOpenModal())->toBeTrue()
        ->and(Action::make('delete')->requiresConfirmation()->modal(false)->shouldOpenModal())->toBeFalse();
});

it('suppresses an implied modal through modalHidden()', function (): void {
    expect(Action::make('delete')->modalHeading('Delete?')->modalHidden()->shouldOpenModal())->toBeFalse();
});

it('opens a modal from a custom heading alone', function (): void {
    expect(Action::make('delete')->modalHeading('Delete?')->shouldOpenModal())->toBeTrue();
});

it('opens a modal when confirmation is required, through the description fallback', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->shouldOpenModal())->toBeTrue();
});

it('falls back the modal description to the confirmation string only when confirming', function (): void {
    expect(Action::make('delete')->requiresConfirmation()->getModalDescription())
        ->toBe('Are you sure you would like to do this?')
        ->and(Action::make('delete')->getModalDescription())->toBeNull();
});

it('does not count the label fallback as a custom modal heading', function (): void {
    expect(Action::make('delete')->hasCustomModalHeading())->toBeFalse()
        ->and(Action::make('delete')->modalHeading('Delete?')->hasCustomModalHeading())->toBeTrue();
});

it('reports a custom modal presence only when modal() was called', function (): void {
    expect(Action::make('delete')->hasCustomModalPresence())->toBeFalse()
        ->and(Action::make('delete')->modal(false)->hasCustomModalPresence())->toBeTrue();
});

it('has no modal content or content footer by default', function (): void {
    expect(Action::make('delete')->getModalContent())->toBeNull()
        ->and(Action::make('delete')->getModalContentFooter())->toBeNull()
        ->and(Action::make('delete')->hasModalContent())->toBeFalse()
        ->and(Action::make('delete')->hasModalContentFooter())->toBeFalse();
});

it('round-trips modal content and content footer markup', function (): void {
    $content = new Markup('<p>Body</p>', 'UTF-8');
    $footer = new Markup('<p>Fine print</p>', 'UTF-8');

    expect(Action::make('delete')->modalContent($content)->getModalContent())->toBe($content)
        ->and(Action::make('delete')->modalContentFooter($footer)->getModalContentFooter())->toBe($footer);
});

it('evaluates modal content closures with the action injected', function (): void {
    $action = Action::make('delete')
        ->modalContent(fn (Action $action): Markup => new Markup("<p>{$action->getName()}</p>", 'UTF-8'));

    expect((string) $action->getModalContent())->toBe('<p>delete</p>');
});

it('reports modal content presence from the raw value, not the evaluated result', function (): void {
    expect(Action::make('delete')->modalContent(fn (): null => null)->hasModalContent())->toBeTrue()
        ->and(Action::make('delete')->modalContentFooter(fn (): null => null)->hasModalContentFooter())->toBeTrue();
});

it('opens a modal from content or a content footer alone', function (): void {
    $markup = new Markup('<p>Body</p>', 'UTF-8');

    expect(Action::make('delete')->modalContent($markup)->shouldOpenModal())->toBeTrue()
        ->and(Action::make('delete')->modalContentFooter($markup)->shouldOpenModal())->toBeTrue();
});

it('builds the default footer as submit and cancel actions', function (): void {
    $actions = Action::make('delete')->requiresConfirmation()->getModalFooterActions();

    expect(array_keys($actions))->toBe(['cancel', 'submit']);

    $submit = $actions['submit'];
    $cancel = $actions['cancel'];

    expect($submit)->toBeInstanceOf(Action::class)
        ->and($submit->getLabel())->toBe('Confirm')
        ->and($submit->canSubmitForm())->toBeTrue()
        ->and($submit->getFormToSubmit())->toBe('callMountedAction')
        ->and($submit->getClientClickHandler())->toBeNull()
        ->and($submit->isButton())->toBeTrue()
        ->and($cancel)->toBeInstanceOf(Action::class)
        ->and($cancel->getLabel())->toBe('Cancel')
        ->and($cancel->shouldClose())->toBeTrue()
        ->and($cancel->getColor())->toBe('gray');
});

it('keeps a click handler on the submit action when the owner has no form wrapper', function (): void {
    $submit = Action::make('delete')->formWrapper(false)->getModalSubmitAction();

    expect($submit?->canSubmitForm())->toBeFalse()
        ->and($submit?->getClientClickHandler())->toBe('callMountedAction');
});

it('colors the submit action primary when the action color is gray or unset', function (): void {
    expect(Action::make('delete')->getModalSubmitAction()?->getColor())->toBe('primary')
        ->and(Action::make('delete')->color('gray')->getModalSubmitAction()?->getColor())->toBe('primary')
        ->and(Action::make('delete')->color('danger')->getModalSubmitAction()?->getColor())->toBe('danger');
});

it('removes the submit or cancel action when customized to false', function (): void {
    expect(array_keys(Action::make('delete')->modalSubmitAction(false)->getModalFooterActions()))->toBe(['cancel'])
        ->and(array_keys(Action::make('delete')->modalCancelAction(false)->getModalFooterActions()))->toBe(['submit']);
});

it('customizes the submit action through a closure receiving the default', function (): void {
    $action = Action::make('delete')->modalSubmitAction(fn (Action $action): Action => $action->label('Go'));

    expect($action->getModalSubmitAction()?->getLabel())->toBe('Go');
});

it('slots extra footer actions between submit and cancel', function (): void {
    $actions = Action::make('delete')
        ->extraModalFooterActions([Action::make('extra')])
        ->getModalFooterActions();

    expect(array_keys($actions))->toBe(['submit', 'extra', 'cancel']);
});

it('replaces the whole footer through modalFooterActions(), keyed and prepared', function (): void {
    $host = new BothContractsHostFixture();
    $custom = Action::make('publish');

    $action = Action::make('delete')->liveComponent($host)->modalFooterActions([$custom]);

    expect($action->getModalFooterActions())->toBe(['publish' => $custom])
        ->and($custom->getLiveComponent())->toBe($host);
});

it('caches the footer actions across calls', function (): void {
    $action = Action::make('delete');

    expect($action->getModalFooterActions())->toBe($action->getModalFooterActions())
        ->and($action->getModalFooterActions()['submit'])->toBe($action->getModalAction('submit'));
});

it('filters hidden footer actions out of the visible list', function (): void {
    $actions = Action::make('delete')
        ->extraModalFooterActions([Action::make('extra')->hidden()])
        ->getVisibleModalFooterActions();

    expect(array_keys($actions))->toBe(['submit', 'cancel']);
});

it('looks up registered modal actions by name', function (): void {
    $registered = Action::make('nested');

    $action = Action::make('delete')->registerModalActions([$registered]);

    expect($action->getModalAction('nested'))->toBe($registered)
        ->and($action->getModalAction('missing'))->toBeNull();
});

it('flattens action groups into getModalActions()', function (): void {
    $grouped = Action::make('grouped');

    $action = Action::make('delete')->extraModalFooterActions([ActionGroup::make([$grouped])]);

    expect($action->getModalAction('grouped'))->toBe($grouped);
});

it('prepares modal actions with the schema binding and a record fallback', function (): void {
    $schema = Schema::make();
    $section = Section::make('Box');

    $owner = Action::make('delete')
        ->schemaContainer($schema)
        ->schemaComponent($section)
        ->record('Ada');

    $prepared = $owner->prepareModalAction(Action::make('nested'));

    expect($prepared->getSchemaContainer())->toBe($schema)
        ->and($prepared->getSchemaComponent())->toBe($section)
        ->and($prepared->getRecord())->toBe('Ada');

    $keepsOwn = $owner->prepareModalAction(Action::make('other')->record('Grace'));

    expect($keepsOwn->getRecord())->toBe('Grace');
});

it('defaults the chrome toggles to Filament values', function (): void {
    $action = Action::make('delete');

    expect($action->isModalSlideOver())->toBeFalse()
        ->and($action->getModalSlideOverPosition())->toBeNull()
        ->and($action->isModalHeaderSticky())->toBeFalse()
        ->and($action->isModalFooterSticky())->toBeFalse()
        ->and($action->hasModalCloseButton())->toBeTrue()
        ->and($action->isModalClosedByClickingAway())->toBeTrue()
        ->and($action->isModalClosedByEscaping())->toBeTrue()
        ->and($action->isModalAutofocused())->toBeTrue()
        ->and($action->isModalClickThrough())->toBeFalse();
});

it('round-trips the chrome toggles, closures included', function (): void {
    $action = Action::make('delete')
        ->slideOver()
        ->slideOverPosition(SlideOverPosition::Start)
        ->modalCloseButton(false)
        ->closeModalByClickingAway(fn (): bool => false)
        ->closeModalByEscaping(false)
        ->modalAutofocus(false)
        ->modalClickThrough(fn (): bool => true);

    expect($action->isModalSlideOver())->toBeTrue()
        ->and($action->getModalSlideOverPosition())->toBe(SlideOverPosition::Start)
        ->and($action->hasModalCloseButton())->toBeFalse()
        ->and($action->isModalClosedByClickingAway())->toBeFalse()
        ->and($action->isModalClosedByEscaping())->toBeFalse()
        ->and($action->isModalAutofocused())->toBeFalse()
        ->and($action->isModalClickThrough())->toBeTrue();
});

it('defaults sticky header and footer to the slide-over state', function (): void {
    $slideOver = Action::make('delete')->slideOver();

    expect($slideOver->isModalHeaderSticky())->toBeTrue()
        ->and($slideOver->isModalFooterSticky())->toBeTrue()
        ->and($slideOver->stickyModalHeader(false)->isModalHeaderSticky())->toBeFalse()
        ->and($slideOver->stickyModalFooter(false)->isModalFooterSticky())->toBeFalse()
        ->and(Action::make('other')->stickyModalHeader()->isModalHeaderSticky())->toBeTrue();
});

it('defaults the modal alignment from confirmation and width', function (): void {
    expect(Action::make('view')->getModalAlignment())->toBe(Alignment::Start)
        ->and(Action::make('view')->modalWidth('xs')->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalWidth('sm')->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('delete')->requiresConfirmation()->getModalAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalAlignment(Alignment::End)->getModalAlignment())->toBe(Alignment::End)
        ->and(Action::make('view')->modalAlignment('custom')->getModalAlignment())->toBe('custom');
});

it('defaults the footer actions alignment to center only for confirmation', function (): void {
    expect(Action::make('view')->getModalFooterActionsAlignment())->toBeNull()
        ->and(Action::make('delete')->requiresConfirmation()->getModalFooterActionsAlignment())->toBe(Alignment::Center)
        ->and(Action::make('view')->modalFooterActionsAlignment('end')->getModalFooterActionsAlignment())->toBe('end');
});

it('reverses composed footer actions for center alignment', function (): void {
    $centered = Action::make('delete')
        ->extraModalFooterActions([Action::make('extra')])
        ->modalFooterActionsAlignment(Alignment::Center);

    expect(array_keys($centered->getModalFooterActions()))->toBe(['cancel', 'extra', 'submit']);

    $stringCentered = Action::make('delete')->modalFooterActionsAlignment('center');

    expect(array_keys($stringCentered->getModalFooterActions()))->toBe(['cancel', 'submit']);

    $overridden = Action::make('delete')
        ->modalFooterActions([Action::make('one'), Action::make('two')])
        ->modalFooterActionsAlignment(Alignment::Center);

    expect(array_keys($overridden->getModalFooterActions()))->toBe(['one', 'two']);
});

it('stamps the parent action on prepared modal actions', function (): void {
    $parent = Action::make('edit')->extraModalFooterActions([Action::make('confirm')]);

    expect($parent->getModalAction('confirm')?->getParentAction())->toBe($parent);
});

it('builds a modal submit action that calls the parent', function (): void {
    $submit = Action::make('edit')->makeModalSubmitAction('save', ['key' => 'value']);

    expect($submit->getName())->toBe('save')
        ->and($submit->getParentActionCallLiveComponentClickHandler())->toBe('callMountedAction')
        ->and($submit->getArguments())->toBe(['key' => 'value'])
        ->and($submit->getColor())->toBe('gray');
});

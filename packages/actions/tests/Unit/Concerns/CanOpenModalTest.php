<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;

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

<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use LogicException;
use stdClass;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\Enums\ActionStatus;

it('names itself delete', function (): void {
    expect(DeleteAction::make()->getName())->toBe('delete')
        ->and(DeleteAction::getDefaultName())->toBe('delete');
});

it('keeps an explicit name over the default', function (): void {
    expect(DeleteAction::make('destroy')->getName())->toBe('destroy');
});

it('carries the delete chrome', function (): void {
    $action = DeleteAction::make();

    expect($action->getLabel())->toBe('Delete')
        ->and($action->getColor())->toBe('danger')
        ->and($action->getIcon())->toBe('heroicons:trash-20-solid')
        ->and($action->isConfirmationRequired())->toBeTrue()
        ->and($action->getModalHeading())->toBe('Delete')
        ->and($action->getSuccessNotificationTitle())->toBe('Deleted');
});

it('lets a caller override the default color', function (): void {
    expect(DeleteAction::make()->color('warning')->getColor())->toBe('warning');
});

it('opens no url of its own', function (): void {
    expect(DeleteAction::make()->getUrl())->toBeNull();
});

it('reports success when the process returns truthy', function (): void {
    $action = DeleteAction::make()->using(fn (): bool => true)->record(new stdClass());

    $action->call();

    expect($action->getStatus())->toBe(ActionStatus::Success);
});

it('reports failure when the process returns falsy', function (): void {
    $action = DeleteAction::make()->using(fn (): bool => false)->record(new stdClass());

    $action->call();

    expect($action->getStatus())->toBe(ActionStatus::Failure);
});

it('hands the record to the using() closure', function (): void {
    $record = new stdClass();
    $seen = null;

    $action = DeleteAction::make()
        ->using(function (object $record) use (&$seen): bool {
            $seen = $record;

            return true;
        })
        ->record($record);

    $action->call();

    expect($seen)->toBe($record);
});

it('fails fast when the default process runs without an injected manager registry', function (): void {
    $action = DeleteAction::make()->record(new stdClass());

    expect(fn (): mixed => $action->call())
        ->toThrow(LogicException::class, 'DeleteAction needs the container-built instance');
});

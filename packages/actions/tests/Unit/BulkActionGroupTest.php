<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\BulkActionGroup;

it('carries the bulk-group chrome', function (): void {
    $group = BulkActionGroup::make([Action::make('delete')]);

    expect($group->getLabel())->toBe('Bulk actions')
        ->and($group->getIcon())->toBe(ActionGroup::DEFAULT_ICON)
        ->and($group->getColor())->toBe('gray')
        ->and($group->getDropdownPlacement())->toBe('bottom-start');
});

it('defaults to the group button trigger, not the icon-button one', function (): void {
    $group = BulkActionGroup::make([Action::make('delete')]);

    expect($group->getTriggerTemplate())->toBe('@SylamentActions/partials/ActionGroup/ButtonTrigger.html.twig')
        ->and($group->isButton())->toBeTrue()
        ->and($group->isIconButton())->toBeFalse();
});

it('lets a caller override the default trigger template and colour', function (): void {
    $group = BulkActionGroup::make([Action::make('delete')])
        ->iconButton()
        ->color('danger');

    expect($group->isIconButton())->toBeTrue()
        ->and($group->getColor())->toBe('danger');
});

it('stamps the bulk-only marker on its dropdown attributes, not its trigger ones', function (): void {
    $group = BulkActionGroup::make([Action::make('delete')]);

    expect($group->getExtraDropdownAttributes())->toBe(['data-sy-table-bulk-only' => true])
        ->and($group->getExtraAttributes())->toBe([]);
});

it('lets a caller dropdown attribute win over the bulk-only marker', function (): void {
    $group = BulkActionGroup::make([Action::make('delete')])
        ->extraDropdownAttributes(['data-sy-table-bulk-only' => 'no']);

    expect($group->getExtraDropdownAttributes())->toBe(['data-sy-table-bulk-only' => 'no']);
});

it('is not itself a bulk action — only its members are', function (): void {
    expect(method_exists(BulkActionGroup::class, 'isBulk'))->toBeFalse();
});

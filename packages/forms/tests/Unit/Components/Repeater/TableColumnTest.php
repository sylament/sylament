<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Repeater;

use Sylament\Forms\Components\Repeater\TableColumn;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;

it('is made from a label and carries its header options', function (): void {
    $column = TableColumn::make('Name');

    expect($column->getLabel())->toBe('Name')
        ->and($column->isHeaderLabelHidden())->toBeFalse()
        ->and($column->isMarkedAsRequired())->toBeFalse()
        ->and($column->canHeaderWrap())->toBeFalse()
        ->and($column->getWidth())->toBeNull()
        ->and($column->getAlignmentClass())->toBeNull();
});

it('evaluates every option, injecting itself as $column', function (): void {
    $column = TableColumn::make(static fn (TableColumn $column): string => 'Role')
        ->hiddenHeaderLabel()
        ->markAsRequired(static fn (): bool => true)
        ->wrapHeader()
        ->width(160)
        ->alignment(Alignment::Center)
        ->verticalAlignment(VerticalAlignment::End);

    expect($column->getLabel())->toBe('Role')
        ->and($column->isHeaderLabelHidden())->toBeTrue()
        ->and($column->isMarkedAsRequired())->toBeTrue()
        ->and($column->canHeaderWrap())->toBeTrue()
        ->and($column->getWidth())->toBe('160px')
        ->and($column->getAlignmentClass())->toBe('sy-align-center')
        ->and($column->getVerticalAlignment())->toBe(VerticalAlignment::End);
});

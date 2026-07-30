<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Unit\Components;

use Sylament\Schemas\Components\Fieldset;

it('takes its label from make()', function (): void {
    expect(Fieldset::make('Address')->getLabel())->toBe('Address');
});

it('resolves a label closure through the real Invoker', function (): void {
    expect(Fieldset::make(fn (): string => 'Computed')->getLabel())->toBe('Computed');
});

it('defaults to two columns', function (): void {
    expect(Fieldset::make('Address')->getAllColumns())->toBe(['lg' => 2]);
});

it('lets a caller override the default columns', function (): void {
    expect(Fieldset::make('Address')->columns(3)->getAllColumns())->toBe(['lg' => 3]);
});

it('is contained by default', function (): void {
    expect(Fieldset::make('Address')->isContained())->toBeTrue()
        ->and(Fieldset::make('Address')->contained(false)->isContained())->toBeFalse();
});

it('shows its label by default', function (): void {
    expect(Fieldset::make('Address')->isLabelHidden())->toBeFalse()
        ->and(Fieldset::make('Address')->hiddenLabel()->isLabelHidden())->toBeTrue();
});

it('is never itself required', function (): void {
    expect(Fieldset::make('Address')->isRequired())->toBeFalse();
});

it('marks as required only on request', function (): void {
    expect(Fieldset::make('Address')->isMarkedAsRequired())->toBeFalse()
        ->and(Fieldset::make('Address')->markAsRequired()->isMarkedAsRequired())->toBeTrue()
        ->and(Fieldset::make('Address')->markAsRequired(fn (): bool => true)->isMarkedAsRequired())->toBeTrue()
        ->and(Fieldset::make('Address')->markAsRequired(false)->isMarkedAsRequired())->toBeFalse();
});

it('applies configureUsing() closures on make()', function (): void {
    Fieldset::configureUsing(function (Fieldset $fieldset): void {
        $fieldset->contained(false);
    });

    expect(Fieldset::make('Address')->isContained())->toBeFalse();
});

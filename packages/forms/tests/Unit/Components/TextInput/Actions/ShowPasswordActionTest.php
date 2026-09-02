<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\TextInput\Actions;

use Sylament\Forms\Components\TextInput\Actions\ShowPasswordAction;

it('names and labels itself and takes the ported eye glyph in gray', function (): void {
    $action = ShowPasswordAction::make();

    expect(ShowPasswordAction::getDefaultName())->toBe('showPassword')
        ->and($action->getName())->toBe('showPassword')
        ->and($action->getLabel())->toBe('Show password')
        ->and($action->getIcon())->toBe('heroicons:eye-20-solid')
        ->and($action->getColor())->toBe('gray');
});

it('is inert chrome bound to the text-input controller', function (): void {
    $action = ShowPasswordAction::make();
    $attributes = $action->getExtraAttributeBag()->toScalarAttributes();

    expect($action->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($attributes['data-action'])->toBe('sylament--forms--text-input#reveal')
        ->and($attributes['class'])->toBe('sy-fo-text-input-show-password');
});

it('leaves room for a caller to add attributes of its own beside the binding', function (): void {
    $attributes = ShowPasswordAction::make()
        ->extraAttributes(['class' => 'mine', 'data-x' => '1'], merge: true)
        ->getExtraAttributeBag()
        ->toScalarAttributes();

    expect($attributes['class'])->toContain('sy-fo-text-input-show-password')
        ->toContain('mine')
        ->and($attributes['data-action'])->toBe('sylament--forms--text-input#reveal')
        ->and($attributes['data-x'])->toBe('1');
});

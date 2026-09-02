<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\TextInput\Actions;

use Sylament\Forms\Components\TextInput\Actions\HidePasswordAction;

it('names and labels itself and takes the ported eye-slash glyph in gray', function (): void {
    $action = HidePasswordAction::make();

    expect(HidePasswordAction::getDefaultName())->toBe('hidePassword')
        ->and($action->getName())->toBe('hidePassword')
        ->and($action->getLabel())->toBe('Hide password')
        ->and($action->getIcon())->toBe('heroicons:eye-slash-20-solid')
        ->and($action->getColor())->toBe('gray');
});

it('is inert chrome bound to the text-input controller', function (): void {
    $action = HidePasswordAction::make();
    $attributes = $action->getExtraAttributeBag()->toScalarAttributes();

    expect($action->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($attributes['data-action'])->toBe('sylament--forms--text-input#conceal')
        ->and($attributes['class'])->toBe('sy-fo-text-input-hide-password');
});

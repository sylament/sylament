<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\TextInput\Actions;

use Sylament\Forms\Components\TextInput\Actions\CopyAction;

it('names and labels itself and takes the ported clipboard glyph in gray', function (): void {
    $action = CopyAction::make();

    expect(CopyAction::getDefaultName())->toBe('copy')
        ->and($action->getName())->toBe('copy')
        ->and($action->getLabel())->toBe('Copy')
        ->and($action->getIcon())->toBe('heroicons:clipboard-document-list-20-solid')
        ->and($action->getColor())->toBe('gray');
});

it('confirms with "Copied" for two seconds until told otherwise', function (): void {
    $action = CopyAction::make();

    expect($action->getCopyMessage(null))->toBe('Copied')
        ->and($action->getCopyMessageDuration(null))->toBe(2000);
});

it('takes a literal message and duration', function (): void {
    $action = CopyAction::make()->copyMessage('Token copied')->copyMessageDuration(5000);

    expect($action->getCopyMessage(null))->toBe('Token copied')
        ->and($action->getCopyMessageDuration(null))->toBe(5000);
});

it('falls back where a message or duration closure resolves null', function (): void {
    $action = CopyAction::make()
        ->copyMessage(static fn (): ?string => null)
        ->copyMessageDuration(static fn (): ?int => null);

    expect($action->getCopyMessage(null))->toBe('Copied')
        ->and($action->getCopyMessageDuration(null))->toBe(2000);
});

it('injects the state into the message and duration closures', function (): void {
    $action = CopyAction::make()
        ->copyMessage(static fn (mixed $state): string => "Copied {$state}")
        ->copyMessageDuration(static fn (mixed $state): int => mb_strlen((string) $state) * 1000);

    expect($action->getCopyMessage('abc'))->toBe('Copied abc')
        ->and($action->getCopyMessageDuration('abc'))->toBe(3000);
});

it('is inert chrome carrying the controller binding and both Stimulus action params', function (): void {
    $action = CopyAction::make();
    $attributes = $action->getExtraAttributeBag()->toScalarAttributes();

    expect($action->isLiveComponentClickHandlerEnabled())->toBeFalse()
        ->and($attributes['data-action'])->toBe('sylament--forms--text-input#copy')
        ->and($attributes['class'])->toBe('sy-fo-text-input-copy')
        ->and($attributes['data-sylament--forms--text-input-message-param'])->toBe('Copied')
        ->and($attributes['data-sylament--forms--text-input-duration-param'])->toBe('2000');
});

it('carries the configured message and duration into the action params', function (): void {
    $attributes = CopyAction::make()
        ->copyMessage('Token copied')
        ->copyMessageDuration(5000)
        ->getExtraAttributeBag()
        ->toScalarAttributes();

    expect($attributes['data-sylament--forms--text-input-message-param'])->toBe('Token copied')
        ->and($attributes['data-sylament--forms--text-input-duration-param'])->toBe('5000');
});

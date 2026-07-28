<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Support\Twig\ComponentAttributeBag;

it('defaults to no extra modal overlay attributes', function (): void {
    expect(Action::make('delete')->getExtraModalOverlayAttributes())->toBe([]);
});

it('replaces by default and merges on request', function (): void {
    $action = Action::make('delete')->extraModalOverlayAttributes(['data-a' => '1', 'class' => 'one']);

    expect($action->extraModalOverlayAttributes(['data-b' => '2'])->getExtraModalOverlayAttributes())
        ->toBe(['data-b' => '2']);

    $merged = Action::make('delete')
        ->extraModalOverlayAttributes(['data-a' => '1', 'class' => 'one'])
        ->extraModalOverlayAttributes(fn (): array => ['data-b' => '2', 'class' => 'two'], merge: true)
        ->getExtraModalOverlayAttributes();

    expect($merged['data-a'])->toBe('1')
        ->and($merged['data-b'])->toBe('2')
        ->and($merged['class'])->toBe(['two', 'one']);
});

it('exposes an attribute bag', function (): void {
    expect(Action::make('delete')->extraModalOverlayAttributes(['data-a' => '1'])->getExtraModalOverlayAttributeBag())
        ->toBeInstanceOf(ComponentAttributeBag::class);
});

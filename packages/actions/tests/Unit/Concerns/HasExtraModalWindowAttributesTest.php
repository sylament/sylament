<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Action;
use Sylament\Support\Twig\ComponentAttributeBag;

it('defaults to no extra modal window attributes', function (): void {
    expect(Action::make('delete')->getExtraModalWindowAttributes())->toBe([]);
});

it('replaces by default and merges on request', function (): void {
    $action = Action::make('delete')->extraModalWindowAttributes(['data-a' => '1', 'class' => 'one']);

    expect($action->extraModalWindowAttributes(['data-b' => '2'])->getExtraModalWindowAttributes())
        ->toBe(['data-b' => '2']);

    $merged = Action::make('delete')
        ->extraModalWindowAttributes(['data-a' => '1', 'class' => 'one'])
        ->extraModalWindowAttributes(fn (): array => ['data-b' => '2', 'class' => 'two'], merge: true)
        ->getExtraModalWindowAttributes();

    expect($merged['data-a'])->toBe('1')
        ->and($merged['data-b'])->toBe('2')
        ->and($merged['class'])->toBe(['two', 'one']);
});

it('exposes an attribute bag', function (): void {
    expect(Action::make('delete')->extraModalWindowAttributes(['data-a' => '1'])->getExtraModalWindowAttributeBag())
        ->toBeInstanceOf(ComponentAttributeBag::class);
});

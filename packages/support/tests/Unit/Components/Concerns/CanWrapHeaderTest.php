<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Components\Concerns;

use Sylament\Support\Components\Concerns\CanWrapHeader;
use Sylament\Support\Concerns\EvaluatesClosures;

function wrapHeaderComponent(): object
{
    return new class {
        use CanWrapHeader;
        use EvaluatesClosures;
    };
}

it('does not wrap the header until asked', function (): void {
    $component = wrapHeaderComponent();

    expect($component->canHeaderWrap())->toBeFalse()
        ->and($component->wrapHeader()->canHeaderWrap())->toBeTrue()
        ->and($component->wrapHeader(static fn (): bool => false)->canHeaderWrap())->toBeFalse();
});

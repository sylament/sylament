<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Unit\Components\Concerns;

use Sylament\Forms\Components\Textarea;

it('leaves Grammarly enabled by default', function (): void {
    expect(Textarea::make('excerpt')->isGrammarlyDisabled())->toBeFalse();
});

it('round-trips the disabled flag', function (): void {
    expect(Textarea::make('excerpt')->disableGrammarly()->isGrammarlyDisabled())->toBeTrue()
        ->and(Textarea::make('excerpt')->disableGrammarly(false)->isGrammarlyDisabled())->toBeFalse();
});

it('resolves a closure condition through evaluate', function (): void {
    $field = Textarea::make('excerpt')->disableGrammarly(
        fn (Textarea $component): bool => $component->getName() === 'excerpt',
    );

    expect($field->isGrammarlyDisabled())->toBeTrue();
});

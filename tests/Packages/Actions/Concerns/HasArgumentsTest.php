<?php

declare(strict_types=1);

use Sylament\Actions\Action;

it('hands its closures the arguments it was given, a merge overriding them and a reset clearing them', function (): void {
    $action = Action::make('edit')->arguments(['tone' => 'soft', 'line' => 5])->mergeArguments(['tone' => 'loud']);
    $arguments = static fn (array $arguments): array => $arguments;

    expect($action->evaluate($arguments))->toBe(['tone' => 'loud', 'line' => 5])
        ->and($action->resetArguments()->evaluate($arguments))->toBe([]);
});

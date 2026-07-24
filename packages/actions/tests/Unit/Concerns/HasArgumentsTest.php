<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasArgumentsFixture;

it('has no arguments by default', function (): void {
    expect(new HasArgumentsFixture()->getArguments())->toBe([])
        ->and(new HasArgumentsFixture()->hasArguments())->toBeFalse();
});

it('sets and reads arguments', function (): void {
    expect(new HasArgumentsFixture()->arguments(['id' => 1])->getArguments())->toBe(['id' => 1]);
});

it('merges arguments, later keys winning', function (): void {
    expect(new HasArgumentsFixture()->arguments(['id' => 1])->mergeArguments(['id' => 2, 'q' => 'x'])->getArguments())
        ->toBe(['id' => 2, 'q' => 'x']);
});

it('resets arguments', function (): void {
    expect(new HasArgumentsFixture()->arguments(['id' => 1])->resetArguments()->hasArguments())->toBeFalse();
});

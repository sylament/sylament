<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\HasDataFixture;

it('replaces data wholesale via data() and reads it back via getData()', function (): void {
    $fixture = new HasDataFixture()->data(['title' => 'Foo']);

    expect($fixture->getData())->toBe(['title' => 'Foo']);

    $fixture->data(['title' => 'Bar']);

    expect($fixture->getData())->toBe(['title' => 'Bar']);
});

it('mutates data through mutateDataUsing() by default', function (): void {
    $fixture = new HasDataFixture()
        ->mutateDataUsing(fn (array $data): array => [...$data, 'mutated' => true])
        ->data(['title' => 'Foo']);

    expect($fixture->getData())->toBe(['title' => 'Foo', 'mutated' => true]);
});

it('skips the mutator when shouldMutate is false', function (): void {
    $fixture = new HasDataFixture()
        ->mutateDataUsing(fn (array $data): array => [...$data, 'mutated' => true])
        ->data(['title' => 'Foo'], shouldMutate: false);

    expect($fixture->getData())->toBe(['title' => 'Foo']);
});

it('resets data to an empty array without running the mutator', function (): void {
    $fixture = new HasDataFixture()
        ->mutateDataUsing(fn (array $data): array => [...$data, 'mutated' => true])
        ->data(['title' => 'Foo'])
        ->resetData();

    expect($fixture->getData())->toBe([]);
});

it('falls back to an empty array from getRawData() when no live component is bound', function (): void {
    // This fixture's getLiveComponentOrNull() is hardwired to null (see fixture docblock), so this
    // only exercises the ?? [] fallback. The non-empty read through InteractsWithActions::getMountedActionData()
    // is covered by Task 10's HasActionsTest integration coverage, once a real live component host exists.
    $fixture = new HasDataFixture();

    expect($fixture->getRawData())->toBe([]);
});

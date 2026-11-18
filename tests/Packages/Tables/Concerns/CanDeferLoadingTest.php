<?php

declare(strict_types=1);

use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\PagedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\UX\LiveComponent\Exception\HydrationException;

it('loads a deferred table once loadTable() runs, and publishes a table that does not defer as loaded whatever its flag says', function (): void {
    $jars = array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 3));
    $this->seed(...$jars);
    $loaded = static fn (Testable $tester): mixed => json_decode($tester->render()->crawler()->filter(Selectors::LIVE_PROPS)->attr('data-live-props-value') ?? '', true, flags: JSON_THROW_ON_ERROR)['isTableLoaded'];

    $deferred = $this->createLiveComponent(PagedLarderJarTable::class, ['variants' => ['deferred']]);

    expect($loaded($deferred))->toBeFalse();

    $deferred->loadTable()->assertCanSeeTableRecords($jars, inOrder: true);

    expect($loaded($deferred))->toBeTrue()
        ->and($loaded($this->createLiveComponent(PagedLarderJarTable::class, ['isTableLoaded' => false])))->toBeTrue();
});

it('keeps a deferred table behind its loading status until it loads, an empty one included, whatever the client claims', function (): void {
    $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['variants' => ['deferred', 'actions']]);
    $waiting = $tester->render()->crawler();

    expect($this->statementsRun())->toBe([])
        ->and($waiting->filter(Selectors::LOADING))->toHaveCount(1)
        ->and($waiting->filter(Selectors::EMPTY_STATE))->toHaveCount(0)
        ->and(fn (): Testable => $this->createLiveComponent(PagedLarderJarTable::class, ['variants' => ['deferred']])->set('isTableLoaded', true))
        ->toThrow(HydrationException::class, 'The model "isTableLoaded" was sent for update, but it is not writable.');

    $loaded = $tester->loadTable()->render()->crawler();

    expect($this->statementsRun())->not->toBe([])
        ->and($loaded->filter(Selectors::EMPTY_STATE))->toHaveCount(1)
        ->and($loaded->filter(Selectors::LOADING))->toHaveCount(0);
});

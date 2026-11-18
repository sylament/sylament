<?php

declare(strict_types=1);

use Sylament\Tables\Table;
use Sylament\Tables\Testing\Testable;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\PagedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Twig\Error\RuntimeError;

it('hands the table controller its poll interval in milliseconds, and none while it does not poll or while it reorders', function (array $props, ?string $milliseconds): void {
    $interval = static fn (Testable $tester): ?string => $tester->render()->crawler()->filter(Selectors::TABLE_CONTROLLER)->attr('data-sylament--tables--table-poll-interval-value');

    $tester = $this->createLiveComponent(PagedLarderJarTable::class, $props);

    expect($interval($tester))->toBe($milliseconds)
        ->and($interval($tester->call('toggleTableReordering')))->toBeNull();
})->with([
    'seconds' => [['poll' => '5s'], '5000'],
    'milliseconds' => [['poll' => '500ms'], '500'],
    'a fraction of a second' => [['poll' => '1.5s'], '1500'],
    'no interval given' => [['variants' => ['pollByDefault']], '10000'],
    'no poll' => [[], null],
]);

it('refuses on its render an interval that is not a number of milliseconds or seconds, naming the table and the interval', function (string $interval): void {
    expect(fn (): mixed => $this->createLiveComponent(PagedLarderJarTable::class, ['poll' => $interval])->render())
        ->toThrow(static function (RuntimeError $error) use ($interval): void {
            expect($error->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
                ->and($error->getPrevious()?->getMessage())->toBe('Table [' . Table::class . "] polls every [{$interval}], which is not a duration; use a number of milliseconds or seconds, like [500ms] or [10s].");
        });
})->with([
    'a bare number' => ['10'],
    'minutes' => ['1m'],
    'nothing' => [''],
]);

it('renders a table polling at a malformed interval while it reorders, and refuses the interval on the render that leaves the mode', function (): void {
    $this->seed($jar = new LarderJar('Jar 01'));

    $tester = $this->createLiveComponent(PagedLarderJarTable::class, ['poll' => '1m', 'isTableReordering' => true]);

    expect($tester->render()->crawler()->filter(Selectors::reorderHandle((string) $jar->id)))->toHaveCount(1)
        ->and(fn (): Testable => $tester->call('toggleTableReordering'))->toThrow(RuntimeError::class, 'polls every [1m]');
});

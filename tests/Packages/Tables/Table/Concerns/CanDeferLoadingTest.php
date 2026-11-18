<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\PagedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders a deferred table first as a loading status that reads no record, and a table that does not defer with its rows at once', function (): void {
    $this->seed(...array_map(static fn (int $number): LarderJar => new LarderJar(sprintf('Jar %02d', $number)), range(1, 11)));

    $deferred = $this->createLiveComponent(PagedLarderJarTable::class, ['variants' => ['deferred', 'actions']])->render()->crawler();
    $deferredStatements = $this->statementsRun();
    $eager = $this->createLiveComponent(PagedLarderJarTable::class, ['variants' => ['actions']])->render()->crawler();

    expect($deferredStatements)->toBe([])
        ->and($deferred->filter(Selectors::LOADING)->text())->toBe('Loading')
        ->and($deferred->filter('[data-sylament--tables--table-deferred-value]')->attr('data-sylament--tables--table-deferred-value'))->toBe('true')
        ->and($deferred->filter(Selectors::ROW))->toHaveCount(0)
        ->and($deferred->filter(SupportSelectors::PAGINATION))->toHaveCount(0)
        ->and($deferred->filter(Selectors::SELECTION_BANNER))->toHaveCount(0)
        ->and($this->statementsRun())->toHaveCount(2)
        ->and($eager->filter(Selectors::LOADING))->toHaveCount(0)
        ->and($eager->filter('[data-sylament--tables--table-deferred-value]')->attr('data-sylament--tables--table-deferred-value'))->toBe('false')
        ->and($eager->filter(Selectors::ROW))->toHaveCount(10)
        ->and($eager->filter(SupportSelectors::PAGINATION))->toHaveCount(1)
        ->and($eager->filter(Selectors::SELECTION_BANNER))->toHaveCount(1);
});

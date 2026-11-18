<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('sums the values under its label in one statement of its own', function (): void {
    $this->seed(new LarderJar(position: 1), new LarderJar(position: 2), new LarderJar(position: 4));

    $crawler = $this->renderSummarizer(Sum::make());

    expect($this::textSummaries($crawler))->toBe(['Sum 7'])
        ->and(preg_grep('/\bSUM\(/', $this->statementsRun()))->toHaveCount(1);
});

it('prints a decimal total as the number it is on every platform, a whole one without decimals', function (array $rates, string $total): void {
    $this->seed(...array_map(static fn (string $rate, string $zone): TariffBand => new TariffBand($rate, $zone), $rates, ['north', 'south']));

    $crawler = $this->renderSummarizer(Sum::make(), 'rate', $this->entityManager()->createQueryBuilder()->select('band')->from(TariffBand::class, 'band'));

    expect($crawler)->toContainElement(Selectors::summarizerFigure('text'), $total);
})->with([
    'a fractional total' => [['1.25', '2.50'], '3.75'],
    'a whole total' => [['1.50', '2.50'], '4'],
]);

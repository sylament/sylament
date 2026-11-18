<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;

it("sums the page's values on the page row and every value on the all-table row, under its label", function (): void {
    $this->seed(new LarderJar('Apricot', position: 1), new LarderJar('Bramble', position: 2), new LarderJar('Cherry', position: 4));

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['positionOnly']])->render()->crawler();

    expect($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7']);
});

it('prints a decimal total as the number it is on every platform, a whole one without decimals', function (array $rates, string $total): void {
    $this->seed(...array_map(static fn (string $rate, string $zone): TariffBand => new TariffBand($rate, $zone), $rates, ['north', 'south']));

    $crawler = $this->renderSummarizer(Sum::make(), 'rate', $this->entityManager()->createQueryBuilder()->select('band')->from(TariffBand::class, 'band'));

    expect($crawler)->toContainElement(Selectors::summarizerFigure('text'), $total);
})->with([
    'a fractional total' => [['1.25', '2.50'], '3.75'],
    'a whole total' => [['1.50', '2.50'], '4'],
]);

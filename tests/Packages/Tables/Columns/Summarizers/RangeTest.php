<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Entity\TariffBand;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('ranges from the lowest value to the highest, a number as the number it is and a date as the driver hands it over', function (Closure $seed, string $column, string $entity, string $range): void {
    $this->seed(...$seed());

    $query = $this->entityManager()->createQueryBuilder()->select('record')->from($entity, 'record');

    expect($this->renderSummarizer(Range::make(), $column, $query))->toContainElement(Selectors::summarizerFigure('range'), $range);
})->with([
    'whole numbers' => [fn (): array => [new LarderJar(position: 12), new LarderJar(position: 3), new LarderJar(position: 7)], 'position', LarderJar::class, '3 - 12'],
    'decimals' => [fn (): array => [new TariffBand('1.50', 'north'), new TariffBand('2.25', 'south')], 'rate', TariffBand::class, '1.5 - 2.25'],
    'dates, a missing one left out' => [fn (): array => [new LarderJar(sealedOn: new DateTimeImmutable('2024-03-07 09:30:00')), new LarderJar(), new LarderJar(sealedOn: new DateTimeImmutable('2024-03-05 10:00:00'))], 'sealedOn', LarderJar::class, '2024-03-05 10:00:00 - 2024-03-07 09:30:00'],
]);

it('renders no figure over a set holding no value', function (): void {
    $this->seed(new LarderJar());

    $crawler = $this->renderSummarizer(Range::make()->label('Sealed'), 'sealedOn');

    expect($crawler)->toContainElement(Selectors::summarizerLabel('range'), 'Sealed')
        ->and($crawler->filter(Selectors::summarizerFigure('range')))->toHaveCount(0);
});

it('shows two dates by the days they differ by, two instants of one day by their times, and one instant once', function (array $instants, string $range): void {
    $this->seed(...array_map(static fn (string $instant): LarderJar => new LarderJar(sealedOn: new DateTimeImmutable($instant)), $instants));

    expect($this->renderSummarizer(Range::make()->minimalDateTimeDifference(), 'sealedOn'))->toContainElement(Selectors::summarizerFigure('range'), $range);
})->with([
    'two days' => [['2024-03-05 10:00:00', '2024-03-07 09:30:00'], 'Mar 5, 2024 - Mar 7, 2024'],
    'one day' => [['2024-03-05 10:00:00', '2024-03-05 16:45:00'], 'Mar 5, 2024 10:00:00 - Mar 5, 2024 16:45:00'],
    'one instant' => [['2024-03-05 10:00:00', '2024-03-05 10:00:00'], 'Mar 5, 2024 10:00:00'],
]);

it('leaves two ends that read as no date as they are, rather than failing the render', function (): void {
    $this->seed(new LarderJar('Airport'), new LarderJar('Airbag'));

    expect($this->renderSummarizer(Range::make()->minimalDateTimeDifference(), 'label'))->toContainElement(Selectors::summarizerFigure('range'), 'Airbag - Airport');
});

it('shows two texts by the shortest beginnings that tell them apart, and one text once', function (array $labels, string $range): void {
    $this->seed(...array_map(static fn (string $label): LarderJar => new LarderJar($label), $labels));

    expect($this->renderSummarizer(Range::make()->minimalTextualDifference(), 'label'))->toContainElement(Selectors::summarizerFigure('range'), $range);
})->with([
    'two texts' => [['Airport', 'Airbag'], 'Airb - Airp'],
    'a text and its beginning' => [['Airbag', 'Air'], 'Air - Airb'],
    'one text' => [['Airbag', 'Airbag'], 'Airbag'],
    'a blank text below another' => [['', 'Airbag'], 'Airbag'],
]);

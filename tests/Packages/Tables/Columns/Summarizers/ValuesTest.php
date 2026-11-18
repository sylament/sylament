<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it('lists each distinct value once as the driver hands it over, an enum by its backing value and a date as its string', function (string $column, array $values): void {
    $this->seed(
        new LarderJar(sealedOn: new DateTimeImmutable('2024-03-05 10:00:00'), seal: JarSeal::Wax),
        new LarderJar(sealedOn: new DateTimeImmutable('2024-03-07 09:30:00'), seal: JarSeal::Screw),
        new LarderJar(sealedOn: new DateTimeImmutable('2024-03-05 10:00:00'), seal: JarSeal::Wax),
    );

    $listed = $this->renderSummarizer(Values::make(), $column)->filter(Selectors::SUMMARIZER_VALUE)->each(static fn (Crawler $value): string => $value->text());
    sort($listed);

    expect($listed)->toBe($values);
})->with([
    'an enum column' => ['seal', ['screw', 'wax']],
    'a date column' => ['sealedOn', ['2024-03-05 10:00:00', '2024-03-07 09:30:00']],
]);

it('bullets its list unless told otherwise', function (bool $bulleted, int $bullets): void {
    $this->seed(new LarderJar(seal: JarSeal::Wax));

    $crawler = $this->renderSummarizer(Values::make()->bulleted($bulleted), 'seal');

    expect($crawler)->toContainElement(Selectors::SUMMARIZER_VALUE, 'wax')
        ->and($crawler->filter(Selectors::summarizer('values') . ' ul.sy-bulleted'))->toHaveCount($bullets);
})->with([
    'by default' => [true, 1],
    'unbulleted' => [false, 0],
]);

it('formats each value on its own, so a closure typed for one value sees one', function (): void {
    $this->seed(new LarderJar(seal: JarSeal::Wax), new LarderJar(seal: JarSeal::Screw));

    $listed = $this->renderSummarizer(Values::make()->formatStateUsing(static fn (string $state): string => ucfirst($state)), 'seal')
        ->filter(Selectors::SUMMARIZER_VALUE)
        ->each(static fn (Crawler $value): string => $value->text());
    sort($listed);

    expect($listed)->toBe(['Screw', 'Wax']);
});

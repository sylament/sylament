<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\SummarizedJarTable;
use Twig\Error\RuntimeError;

it("counts a column's filled values under its label, alike whether the batch or its own query answers", function (): void {
    $this->seed(
        new LarderJar('Apricot', new DateTimeImmutable('2024-03-05 10:00:00'), position: 1),
        new LarderJar('Bramble', position: 2),
        new LarderJar('Cherry', new DateTimeImmutable('2024-03-07 09:30:00'), position: 4),
    );

    $crawler = $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['ownCount']])->render()->crawler();

    expect($this::textSummaries($crawler->filter(Selectors::summaryRow('page'))))->toBe(['Sum 3', 'Count 1', 'Own count 1', 'Count 0'])
        ->and($this::textSummaries($crawler->filter(Selectors::summaryRow('all'))))->toBe(['Sum 7', 'Count 2', 'Own count 2', 'Count 0']);
});

it('refuses to count with icons, naming the column that would render them and the way out', function (): void {
    $this->seed(new LarderJar('Apricot', position: 1));

    expect(fn (): mixed => $this->createLiveComponent(SummarizedJarTable::class, ['variants' => ['iconCount']])->render())
        ->toThrow(RuntimeError::class, 'Cannot summarize [sealedOn] with icons(): an icon count needs one count per distinct icon of an IconColumn, which this toolkit does not render yet. Drop icons(), or take the summary over with Summarizer::using().');
});

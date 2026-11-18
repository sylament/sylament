<?php

declare(strict_types=1);

use Sylament\Tables\Columns\Summarizers\Range;
use Sylament\Tables\Columns\Summarizers\Sum;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tables\Columns\Summarizers\Values;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('leads its figure with its default label, the label it is given instead, or none for a blank one', function (?string $label, int $labels, string $text): void {
    $this->seed(new LarderJar(position: 3));

    $crawler = $this->renderSummarizer(Sum::make()->label($label));

    expect($crawler->filter(Selectors::summarizerLabel('text')))->toHaveCount($labels)
        ->and($this::textSummaries($crawler))->toBe([$text]);
})->with([
    'its default' => [null, 1, 'Sum 3'],
    'its own' => ['Total', 1, 'Total 3'],
    'a blank one' => [' ', 0, '3'],
]);

it('keeps a hidden label for screen readers alone, whichever body renders it', function (Closure $summarizer, string $body, string $column): void {
    $this->seed(new LarderJar(seal: JarSeal::Wax, position: 3));

    $label = $this->renderSummarizer($summarizer()->label('Shelf')->hiddenLabel(), $column)->filter(Selectors::summarizerLabel($body));

    expect($label->text())->toBe('Shelf')
        ->and($label->matches(SupportSelectors::SCREEN_READER_ONLY))->toBeTrue();
})->with([
    'a text body' => [fn (): Summarizer => Sum::make(), 'text', 'position'],
    'a range body' => [fn (): Summarizer => Range::make(), 'range', 'position'],
    'a values body' => [fn (): Summarizer => Values::make(), 'values', 'seal'],
]);

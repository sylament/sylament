<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders the heading at the root heading level, as a paragraph beyond the sixth', function (int $level, string $tag): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class, ['rootHeadingLevel' => $level])->render()->crawler();

    expect($crawler)->toContainElement(Selectors::HEADER . " {$tag}", 'Larder jars');
})->with([
    'the sixth' => [6, 'h6'],
    'beyond the sixth' => [7, 'p'],
]);

it('names its table by the heading as text, by the heading markup stripped to text, and by the plural record label without one', function (array $props, string $name): void {
    $this->seed(new LarderJar('Jar 01'));

    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class, $props + ['pluralRecordLabel' => 'Shelf jars'])->render()->crawler();

    expect($crawler->filter(Selectors::TABLE)->attr('aria-label'))->toBe($name);
})->with([
    'a text heading' => [[], 'Larder jars'],
    'a markup heading' => [['markupHeading' => true, 'heading' => ' <em>Larder</em> jars '], 'Larder jars'],
    'no heading' => [['heading' => null], 'Shelf jars'],
]);

<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders the heading at the root heading level, as a paragraph beyond the sixth', function (int $level, string $tag): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class, ['rootHeadingLevel' => $level])->render()->crawler();

    expect($crawler)->toContainElement(Selectors::HEADER . " {$tag}", 'Larder jars');
})->with([
    'the sixth' => [6, 'h6'],
    'beyond the sixth' => [7, 'p'],
]);

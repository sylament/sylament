<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders the heading and the description in the header band', function (): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::HEADER . ' h2', 'Larder jars')
        ->and($crawler)->toContainElement(Selectors::HEADER . ' p', 'The jars on the shelf');
});

it('marks the header band for the adaptive actions position only while it holds header actions', function (bool $creates, int $count): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class, ['creates' => $creates])->render()->crawler();

    expect($crawler->filter(Selectors::HEADER))->toHaveCount(1)
        ->and($crawler->filter(Selectors::HEADER . '.sy-ta-header-adaptive-actions-position'))->toHaveCount($count);
})->with([
    'header actions' => [true, 1],
    'none' => [false, 0],
]);

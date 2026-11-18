<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it('renders the heading and the description in the header band', function (): void {
    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class)->render()->crawler();

    expect($crawler)->toContainElement(Selectors::HEADER . ' h2', 'Larder jars')
        ->and($crawler)->toContainElement(Selectors::HEADER . ' p', 'The jars on the shelf');
});

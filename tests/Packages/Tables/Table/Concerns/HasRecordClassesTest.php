<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\HeadedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;

it("adds each record's own classes to its row after the table's", function (): void {
    $this->seed($sealed = new LarderJar('Apricot', seal: JarSeal::Wax), $open = new LarderJar('Bramble'));

    $crawler = $this->createLiveComponent(HeadedLarderJarTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::row((string) $sealed->id))->attr('class'))->toBe('sy-ta-row jar-sealed')
        ->and($crawler->filter(Selectors::row((string) $open->id) . '.jar-sealed'))->toHaveCount(0);
});

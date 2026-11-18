<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Sylament\Tests\Fixtures\Tables\ShapedJarFilterTable;
use Symfony\Component\DomCrawler\Crawler;

it('leaves a hidden filter out of the form, the chips and the query, though it holds a state', function (string $hidden): void {
    $this->seed($plum = new LarderJar('Plum', seal: JarSeal::Wax), $open = new LarderJar('Open'));

    $tester = $this->createLiveComponent(ShapedJarFilterTable::class)
        ->set('tableFilters', ['sealed' => ['isActive' => true], $hidden => ['isActive' => true]])
        ->assertCanSeeTableRecords([$plum])
        ->assertCanNotSeeTableRecords([$open]);

    $crawler = $tester->render()->crawler();

    expect($crawler)->toContainElement(Selectors::filterGroup('sealed'))
        ->and($crawler->filter(Selectors::filterGroup($hidden))->count())->toBe(0)
        ->and($crawler->filter(Selectors::FILTER_INDICATOR_LABEL)->each(static fn (Crawler $chip): string => $chip->text()))->toBe(['Sealed jars']);
})->with([
    'hidden' => ['hiddenOutright'],
    'not visible' => ['invisible'],
    'hidden on its host' => ['hiddenHere'],
    'visible only on another host' => ['shownElsewhere'],
]);

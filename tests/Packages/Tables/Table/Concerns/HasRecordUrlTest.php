<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\LinkedLarderJarTable;
use Sylament\Tests\Fixtures\Tables\Selectors;
use Symfony\Component\DomCrawler\Crawler;

it("links every cell of a record's row to the record's URL", function (): void {
    $this->seed($jar = new LarderJar('Apricot'));

    $links = $this->createLiveComponent(LinkedLarderJarTable::class)
        ->render()
        ->crawler()
        ->filter(Selectors::row((string) $jar->id) . ' a' . Selectors::CELL_WRAPPER);

    expect($links->each(static fn (Crawler $link): ?string => $link->attr('href')))->toBe(["/jars/{$jar->id}", "/jars/{$jar->id}"]);
});

it('links nowhere from a record whose URL is unsafe', function (): void {
    $this->seed($safe = new LarderJar('Apricot'), $hazard = new LarderJar('Hazard'));

    $crawler = $this->createLiveComponent(LinkedLarderJarTable::class)->render()->crawler();

    expect($crawler->filter(Selectors::row((string) $safe->id) . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(2)
        ->and($crawler->filter(Selectors::row((string) $hazard->id) . ' ' . Selectors::CELL_WRAPPER))->toHaveCount(2)
        ->and($crawler->filter(Selectors::row((string) $hazard->id) . ' a' . Selectors::CELL_WRAPPER))->toHaveCount(0);
});

describe('extra link attributes', function (): void {
    it('lands them on the record link, a second set replacing the first unless merged', function (bool $merges, ?string $shelf): void {
        $this->seed($jar = new LarderJar('Apricot'));

        $link = $this->createLiveComponent(LinkedLarderJarTable::class, ['mergesLinkAttributes' => $merges])
            ->render()
            ->crawler()
            ->filter(Selectors::cell((string) $jar->id, 'label') . ' ' . Selectors::CELL_WRAPPER);

        expect($link->attr('data-jar'))->toBe('Apricot')
            ->and($link->attr('data-shelf'))->toBe($shelf);
    })->with([
        'merged' => [true, 'top'],
        'replaced' => [false, null],
    ]);

    it('never lets them replace the derived href, nor a target the new tab sets', function (bool $opensInNewTab, string $target): void {
        $this->seed($jar = new LarderJar('Apricot'));

        $link = $this->createLiveComponent(LinkedLarderJarTable::class, ['opensInNewTab' => $opensInNewTab])
            ->render()
            ->crawler()
            ->filter(Selectors::cell((string) $jar->id, 'label') . ' ' . Selectors::CELL_WRAPPER);

        expect($link->attr('href'))->toBe("/jars/{$jar->id}")
            ->and($link->attr('target'))->toBe($target);
    })->with([
        'a new tab, opened before the URL is set' => [true, '_blank'],
        'no new tab, where their target stands' => [false, '_self'],
    ]);

    it("reads them once per record, however many of the record's cells link", function (): void {
        $this->seed(new LarderJar('Apricot'), new LarderJar('Bramble'));

        $this->createLiveComponent(LinkedLarderJarTable::class)->render();

        expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['link attributes Apricot', 'link attributes Bramble']);
    });
});

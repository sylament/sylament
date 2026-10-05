<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Line;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditHushVerse;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

uses(InteractsWithLiveComponents::class);

/*
 * The `hush` panel's verse and line resources override getRecordQuery() to hide
 * every row titled `Hidden …`. Every hidden row below sits under its real
 * parent, so only the resource's own query can refuse it — the parent scope
 * alone would serve it.
 */

/**
 * @return array{client: KernelBrowser, chapter: Chapter, verse: Verse, line: Line, hiddenVerse: Verse, hiddenLine: Line, lineUnderHiddenVerse: Line}
 */
function hushRecords(): array
{
    $client = test()->getContainer()->get('test.client');
    $client->disableReboot();
    $client->catchExceptions(true);

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);

    $chapter = new Chapter()->setTitle('Genesis');
    $verse = new Verse()->setHeading('In the beginning')->setChapter($chapter);
    $hiddenVerse = new Verse()->setHeading('Hidden verse')->setChapter($chapter);
    $line = new Line()->setContent('Let there be light')->setVerse($verse);
    $hiddenLine = new Line()->setContent('Hidden line')->setVerse($verse);
    $mutedLine = new Line()->setContent('Muted line')->setVerse($verse);
    $lineUnderHiddenVerse = new Line()->setContent('Under the hidden verse')->setVerse($hiddenVerse);

    foreach ([$chapter, $verse, $hiddenVerse, $line, $hiddenLine, $mutedLine, $lineUnderHiddenVerse] as $record) {
        $entityManager->persist($record);
    }

    $entityManager->flush();

    return [
        'client' => $client,
        'chapter' => $chapter,
        'verse' => $verse,
        'line' => $line,
        'hiddenVerse' => $hiddenVerse,
        'hiddenLine' => $hiddenLine,
        'lineUnderHiddenVerse' => $lineUnderHiddenVerse,
    ];
}

it('serves the nested edit page of a row the resource query keeps', function (): void {
    ['client' => $client, 'chapter' => $chapter, 'verse' => $verse, 'line' => $line] = hushRecords();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/edit', $chapter->getId(), $verse->getId()));
    $verseStatus = $client->getResponse()->getStatusCode();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/hush-lines/%s/edit', $chapter->getId(), $verse->getId(), $line->getId()));

    expect($verseStatus)->toBe(200)
        ->and($client->getResponse()->getStatusCode())->toBe(200);
});

it('404s the nested edit page of a row the resource query hides, under its real parent', function (): void {
    ['client' => $client, 'chapter' => $chapter, 'hiddenVerse' => $hiddenVerse] = hushRecords();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/edit', $chapter->getId(), $hiddenVerse->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('404s a depth-two edit page of a row the resource query hides, under its real parents', function (): void {
    ['client' => $client, 'chapter' => $chapter, 'verse' => $verse, 'hiddenLine' => $hiddenLine] = hushRecords();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/hush-lines/%s/edit', $chapter->getId(), $verse->getId(), $hiddenLine->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

it('404s every page beneath an intermediate ancestor its own resource query hides', function (): void {
    ['client' => $client, 'chapter' => $chapter, 'hiddenVerse' => $hiddenVerse, 'lineUnderHiddenVerse' => $line] = hushRecords();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/hush-lines/%s/edit', $chapter->getId(), $hiddenVerse->getId(), $line->getId()));
    $editStatus = $client->getResponse()->getStatusCode();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/hush-lines', $chapter->getId(), $hiddenVerse->getId()));

    expect($editStatus)->toBe(404)
        ->and($client->getResponse()->getStatusCode())->toBe(404);
});

it('lists under a parent only the rows both the resource query and the table scope keep', function (): void {
    ['client' => $client, 'chapter' => $chapter, 'verse' => $verse] = hushRecords();

    $crawler = $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/hush-lines', $chapter->getId(), $verse->getId()));
    $rows = $crawler->filter('tbody .sy-ta-row');

    expect($client->getResponse()->getStatusCode())->toBe(200)
        ->and($rows->count())->toBe(1)
        ->and($rows->text())->toContain('Let there be light');
});

it("refuses a mounted frame naming a row the resource query hides, under the page's own parent", function (): void {
    ['client' => $client, 'chapter' => $chapter, 'verse' => $verse, 'hiddenVerse' => $hiddenVerse] = hushRecords();

    $host = $this->createLiveComponent(EditHushVerse::class, [
        'record' => (string) $verse->getId(),
        'parentRecords' => ['chapter' => (string) $chapter->getId()],
        'panelId' => 'hush',
    ], client: $client)->component();

    expect(fn (): mixed => $host->mountAction('edit', [], ['recordKey' => (string) $hiddenVerse->getId()]))
        ->toThrow(NotFoundHttpException::class, 'No record matching');
});

it('still 404s a row the resource query keeps under a parent it does not belong to', function (): void {
    ['client' => $client, 'verse' => $verse] = hushRecords();

    $entityManager = test()->getContainer()->get(EntityManagerInterface::class);
    $foreignChapter = new Chapter()->setTitle('Exodus');
    $entityManager->persist($foreignChapter);
    $entityManager->flush();

    $client->request('GET', sprintf('/hush/hush-chapters/%s/hush-verses/%s/edit', $foreignChapter->getId(), $verse->getId()));

    expect($client->getResponse()->getStatusCode())->toBe(404);
});

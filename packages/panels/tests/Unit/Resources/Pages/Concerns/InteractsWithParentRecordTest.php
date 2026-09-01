<?php

declare(strict_types=1);

use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Chapter;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Verse;
use Sylament\Panels\Tests\Fixtures\Doctrine\EntityManagerFactory;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\NestedResourceRig;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditChapter;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditLine;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\EditVerse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('resolves no parent for a page whose resource nests under nothing', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditChapter::class);
    $page->mountRecord((string) $chapter->getId());

    expect($page->getParentRecord())->toBeNull()
        ->and($page->getParentResourceInstance())->toBeNull()
        ->and($page->getParentRecordScope())->toBeNull()
        ->and($page->getRecord())->toBe($chapter);
});

it('refuses to title a parent record a page has none of', function (): void {
    $entityManager = EntityManagerFactory::create();

    NestedResourceRig::page($entityManager, EditChapter::class)->getParentRecordTitle();
})->throws(LogicException::class, 'names no parent');

it('resolves the parent record a nested page carries the key of', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => (string) $chapter->getId()]);

    expect($page->getParentRecord())->toBe($chapter)
        ->and($page->getParentResourceInstance())->toBeInstanceOf(ChapterResource::class)
        ->and($page->getParentRecordTitle())->toBe('Genesis')
        ->and($page->getRecord())->toBe($verse);
});

it('resolves both ancestors of a depth-two page, nearest last', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, $verse, $line] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditLine::class);
    $page->mountRecord((string) $line->getId(), [
        'chapter' => (string) $chapter->getId(),
        'verse' => (string) $verse->getId(),
    ]);

    expect($page->getParentRecord())->toBe($verse)
        ->and($page->getRecord())->toBe($line)
        // The line has no title attribute, so the parent's is what names it.
        ->and($page->getParentRecordTitle())->toBe('In the beginning');
});

it('404s a record of another parent', function (): void {
    $entityManager = EntityManagerFactory::create();
    [, $verse] = NestedResourceRig::records($entityManager);

    $otherChapter = new Chapter()->setTitle('Exodus');
    EntityManagerFactory::seed($entityManager, $otherChapter);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => (string) $otherChapter->getId()]);

    expect(fn (): object => $page->getRecord())
        ->toThrow(NotFoundHttpException::class, 'No record matching');
});

it('404s an ancestor key that names no record at all', function (): void {
    $entityManager = EntityManagerFactory::create();
    [, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => '999999']);

    expect(fn (): ?object => $page->getParentRecord())
        ->toThrow(NotFoundHttpException::class, 'Parent record [999999] not found');
});

it('404s a nested page carrying no ancestor key at all', function (): void {
    $entityManager = EntityManagerFactory::create();
    [, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId());

    expect(fn (): ?object => $page->getParentRecord())
        ->toThrow(NotFoundHttpException::class, 'Parent record [?] not found');
});

it('scopes each step of the chain to the one above it', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, , $line] = NestedResourceRig::records($entityManager);

    $foreignChapter = new Chapter()->setTitle('Exodus');
    $foreignVerse = new Verse()->setHeading('Foreign')->setChapter($foreignChapter);
    EntityManagerFactory::seed($entityManager, $foreignChapter, $foreignVerse);

    // The verse is real and the line is really its chapter's — but the verse
    // belongs to another chapter, so the walk refuses at the verse step rather
    // than reaching the line through a branch nobody asked for.
    $page = NestedResourceRig::page($entityManager, EditLine::class);
    $page->mountRecord((string) $line->getId(), [
        'chapter' => (string) $chapter->getId(),
        'verse' => (string) $foreignVerse->getId(),
    ]);

    expect(fn (): ?object => $page->getParentRecord())
        ->toThrow(NotFoundHttpException::class, sprintf('Parent record [%s] not found', $foreignVerse->getId()));
});

it('resolves the chain once per request', function (): void {
    $entityManager = EntityManagerFactory::create();
    [$chapter, $verse] = NestedResourceRig::records($entityManager);

    $page = NestedResourceRig::page($entityManager, EditVerse::class);
    $page->mountRecord((string) $verse->getId(), ['chapter' => (string) $chapter->getId()]);

    $first = $page->getParentRecord();

    $entityManager->clear();

    expect($page->getParentRecord())->toBe($first);
});

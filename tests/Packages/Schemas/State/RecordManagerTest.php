<?php

declare(strict_types=1);

use Sylament\Schemas\State\RecordManager;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\SecondaryEntity\Observatory;

it('answers the manager that maps a class', function (): void {
    $registry = $this::getContainer()->get('doctrine');

    expect(RecordManager::of($registry, Article::class))->toBe($this->entityManager())
        ->and(RecordManager::of($registry, Observatory::class))->toBe($this->entityManagerFor(Observatory::class));
});

it('answers no manager for a class none maps, and refuses it by name when one is required', function (): void {
    $registry = $this::getContainer()->get('doctrine');

    expect(RecordManager::find($registry, stdClass::class))->toBeNull()
        ->and(fn (): mixed => RecordManager::of($registry, stdClass::class))->toThrow(LogicException::class, 'No Doctrine manager handles stdClass.');
});

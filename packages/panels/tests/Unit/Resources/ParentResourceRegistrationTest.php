<?php

declare(strict_types=1);

use Sylament\Panels\Resources\ParentResourceRegistration;
use Sylament\Panels\Tests\Fixtures\Resources\ChapterResource;
use Sylament\Panels\Tests\Fixtures\Resources\VerseResource;

it('carries both resources and no relationship name by default', function (): void {
    $registration = new ParentResourceRegistration(ChapterResource::class, VerseResource::class);

    expect($registration->parentResource)->toBe(ChapterResource::class)
        ->and($registration->childResource)->toBe(VerseResource::class)
        ->and($registration->getRelationshipName())->toBeNull();
});

it('names the relationship on a copy, leaving the original untouched', function (): void {
    $registration = new ParentResourceRegistration(ChapterResource::class, VerseResource::class);
    $named = $registration->relationship('verses');

    expect($named)->not->toBe($registration)
        ->and($named->getRelationshipName())->toBe('verses')
        ->and($registration->getRelationshipName())->toBeNull()
        ->and($named->parentResource)->toBe(ChapterResource::class)
        ->and($named->childResource)->toBe(VerseResource::class);
});

it('renames the relationship on a registration that already names one', function (): void {
    $registration = new ParentResourceRegistration(ChapterResource::class, VerseResource::class, 'verses');

    expect($registration->getRelationshipName())->toBe('verses')
        ->and($registration->relationship('appendixVerses')->getRelationshipName())->toBe('appendixVerses');
});

it('builds a registration off the parent resource itself', function (): void {
    $registration = new ChapterResource()->asParent(VerseResource::class);

    expect($registration->parentResource)->toBe(ChapterResource::class)
        ->and($registration->childResource)->toBe(VerseResource::class)
        ->and($registration->getRelationshipName())->toBeNull();
});

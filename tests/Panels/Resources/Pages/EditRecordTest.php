<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Panels\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Panels\HookLog;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditHookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditOrderLine;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Schemas\Entity\OrderLine;

it('renders the edit page filled with the record', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->visit("/admin/articles/{$article->id}/edit");

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toBe('Edit Article') // the record label, not the record title
        ->and($page->filter(Selectors::field('title'))->attr('value'))->toBe('Draft');
});

it('saves the edit, stays on the page and toasts', function (): void {
    $this->seed($article = new Article('Draft'));

    $this->createLiveComponent(EditArticle::class, ['record' => $article->id])
        ->fillForm(['title' => 'Published'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNoRedirect()
        ->assertNotified('Saved');

    expect($this->reload($article)->title)->toBe('Published');
});

it('leaves the row untouched when the edit is invalid', function (): void {
    $this->seed($article = new Article('Draft'));

    $this->createLiveComponent(EditArticle::class, ['record' => $article->id])
        ->fillForm(['title' => ''])
        ->call('save')
        ->assertHasFormErrors(['title' => RequiredValue::class]);

    expect($this->reload($article)->title)->toBe('Draft');
});

it("runs the save hooks in Filament's order, and a halt skips the write", function (): void {
    $this->seed($article = new HookedArticle(EditHookedArticle::HALTING_TITLE));

    $this->createLiveComponent(EditHookedArticle::class, ['record' => $article->id])
        ->fillForm(['title' => 'Published'])
        ->call('save');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['beforeValidate', 'afterValidate', 'beforeSave'])
        ->and($this->reload($article)->title)->toBe(EditHookedArticle::HALTING_TITLE);
});

it('refuses an edited composite key and keeps the row under its key', function (): void {
    $this->seed($line = new OrderLine(order: 7, line: 2, note: 'First'));

    $this->createLiveComponent(EditOrderLine::class, ['record' => RecordKey::of($this->entityManager(), $line)])
        ->fillForm(['line' => 3])
        ->call('save')
        ->assertHasFormErrors(['line']);

    expect($this->entityManager()->getRepository(OrderLine::class)->findAll())
        ->sequence(fn ($stored) => $stored->toBeRecord(order: 7, line: 2));
});

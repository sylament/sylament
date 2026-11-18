<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\GuardedEditArticle;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Symfony\UX\LiveComponent\Exception\HydrationException;

it('marks the saved data as an edit or create page opens in a panel that turns the alerts on, and marks nothing in one that leaves them off', function (): void {
    $this->seed($article = new Article('Draft'));

    $edit = $this->unsavedChangesGuard($this->visit("/simple-knobs/articles/{$article->id}/edit"));
    $create = $this->unsavedChangesGuard($this->visit('/simple-knobs/articles/create'));
    $unguarded = $this->visit("/admin/articles/{$article->id}/edit");

    expect($edit)->toMatchArray(['dataModel' => 'data', 'guardActions' => 'true'])
        ->and($edit['savedHash'] ?? '')->toMatch('/^[0-9a-f]{32}$/')
        ->and($create)->toMatchArray(['dataModel' => 'data', 'guardActions' => 'true'])
        ->and($create['savedHash'] ?? '')->toMatch('/^[0-9a-f]{32}$/')
        ->and($create['savedHash'])->not->toBe($edit['savedHash'])
        ->and($unguarded->filter(Selectors::PAGE))->toHaveCount(1)
        ->and($this->unsavedChangesGuard($unguarded))->toBeNull()
        ->and($this->renderedLiveProps($unguarded))->toHaveKey('savedDataHash', null);
});

it('guards the data of a page that turns the alert on for itself in a panel that leaves it off, and refuses a client write to the mark', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->createLiveComponent(GuardedEditArticle::class, ['record' => $article->id]);

    expect($this->unsavedChangesGuard($page->render()->crawler()))->toMatchArray(['dataModel' => 'data', 'guardActions' => 'false'])
        ->and(fn () => $page->set('savedDataHash', 'forged'))->toThrow(HydrationException::class, 'The model "savedDataHash" was sent for update, but it is not writable');
});

it('marks the saved data again once a save writes it, and keeps the mark when a save writes nothing', function (): void {
    $this->seed($article = new Article('Draft'));
    $savedHash = fn (): ?string => $this->unsavedChangesGuard($this->visit("/simple-knobs/articles/{$article->id}/edit"))['savedHash'] ?? null;
    $draftHash = $savedHash();

    $page = $this->createLiveComponent(GuardedEditArticle::class, ['record' => $article->id]);
    $onArrival = $this->unsavedChangesGuard($page->render()->crawler())['savedHash'] ?? null;

    $page->fillForm(['title' => ''])->call('save')->assertHasFormErrors(['title']);
    $afterRefusal = $this->unsavedChangesGuard($page->render()->crawler())['savedHash'] ?? null;

    $page->fillForm(['title' => 'Published'])->call('save')->assertNotified('Saved');
    $afterSave = $this->unsavedChangesGuard($page->render()->crawler())['savedHash'] ?? null;

    expect($onArrival)->toBe($draftHash)
        ->and($afterRefusal)->toBe($draftHash)
        ->and($afterSave)->not->toBe($draftHash)
        ->and($afterSave)->toBe($savedHash());
});

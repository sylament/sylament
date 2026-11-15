<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\HookedArticle;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Panels\HookLog;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditHookedArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditInvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditOrderLine;
use Sylament\Tests\Fixtures\Panels\Selectors;

it('renders the edit page filled with the record', function (): void {
    $this->seed($article = new Article('Draft'));

    $page = $this->visit("/admin/articles/{$article->id}/edit");

    expect($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith('Edit Article') // the record label, not the record title
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

it('refuses an edited composite key and keeps the row under its key', function (string $page, object $line): void {
    $this->seed($line);

    $this->createLiveComponent($page, ['record' => RecordKey::of($this->entityManager(), $line)])
        ->fillForm(['line' => 3])
        ->call('save')
        ->assertHasFormErrors(['line']);

    expect($this->entityManager()->getRepository($line::class)->findAll())
        ->sequence(fn ($stored) => $stored->toBeRecord(line: 2, note: 'First'));
})->with([
    'scalar fields' => fn (): array => [EditOrderLine::class, new OrderLine(order: 7, line: 2, note: 'First')],
    'an association plus a scalar' => fn (): array => [EditInvoiceLine::class, new InvoiceLine(new Invoice(7), line: 2, note: 'First')],
]);

<?php

declare(strict_types=1);

use Sylament\Schemas\Identity\RecordKey;
use Sylament\Tests\Fixtures\Entity\Article;
use Sylament\Tests\Fixtures\Entity\CompanionHerb;
use Sylament\Tests\Fixtures\Entity\Invoice;
use Sylament\Tests\Fixtures\Entity\InvoiceLine;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Entity\VettedBinder;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Sylament\Tests\Fixtures\Panels\Resources\InvoiceLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\OrderLineResource;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditArticle;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditInvoiceLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\EditOrderLine;
use Sylament\Tests\Fixtures\Panels\Selectors;
use Sylament\Tests\Fixtures\Support\Selectors as SupportSelectors;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('the record key', function (): void {
    it('rides the wire as the route key string, and comes back through a live request', function (string $page, string $uri, object $record): void {
        $this->seed($record);
        $key = RecordKey::of($this->entityManager(), $record);

        $tester = $this->createLiveComponent($page, ['record' => $key])->call('save')->assertHasNoFormErrors();

        expect($this->renderedLiveProps($this->visit(sprintf($uri, $key)))['record'])->toBe($key)
            ->and($this->renderedLiveProps($tester->render()->crawler())['record'])->toBe($key);
    })->with([
        'a single identifier' => fn (): array => [EditArticle::class, '/admin/articles/%s/edit', new Article('Draft')],
        'scalar fields' => fn (): array => [EditOrderLine::class, '/admin/order-lines/%s/edit', new OrderLine(order: 7, line: 2, note: 'First')],
        'an association plus a scalar' => fn (): array => [EditInvoiceLine::class, '/admin/invoice-lines/%s/edit', new InvoiceLine(new Invoice(7), line: 2, note: 'First')],
    ]);

    it('is a 404 for a key naming no row, and for a key no record could carry, which runs no statement', function (string $uri, string $resource, string $missing, string $malformed): void {
        expect(fn () => $this->visit(sprintf($uri, $missing)))->toThrow(NotFoundHttpException::class, "No record matching [{$missing}] exists for the resource [{$resource}].")
            ->and($this->statementsRun())->not->toBe([])
            ->and(fn () => $this->visit(sprintf($uri, $malformed)))->toThrow(NotFoundHttpException::class, "No record matching [{$malformed}] exists for the resource [{$resource}].")
            ->and($this->statementsRun())->toBe([]);
    })->with([
        'a single identifier' => ['/admin/articles/%s/edit', ArticleResource::class, '999', 'abc'],
        'scalar fields' => ['/admin/order-lines/%s/edit', OrderLineResource::class, '7~9', '7'],
        'an association plus a scalar' => ['/admin/invoice-lines/%s/edit', InvoiceLineResource::class, '7~9', '7'],
    ]);

    it('resolves no record under a loose spelling of its key, redirects nowhere, and resolves the canonical one in a single statement', function (): void {
        $this->seed($article = new Article('Draft'));

        expect(fn () => $this->visit("/admin/articles/0{$article->id}/edit"))->toThrow(NotFoundHttpException::class)
            ->and(fn () => $this->visit("/admin/articles/+{$article->id}/edit"))->toThrow(NotFoundHttpException::class)
            ->and($this->visit("/admin/articles/{$article->id}/edit")->filter(Selectors::field('title'))->attr('value'))->toBe('Draft')
            ->and($this->statementsRun())->toHaveCount(1);
    });
});

it("titles the page by the record's title, else by the resource's record label, and the document by the label", function (object $record, string $uri, string $heading, string $document): void {
    $this->seed($record);

    $page = $this->visit(sprintf($uri, RecordKey::of($this->entityManager(), $record)));

    expect($page)->toContainElement(Selectors::PAGE_HEADING, $heading)
        ->and($page->filter(Selectors::DOCUMENT_TITLE)->text())->toStartWith($document);
})->with([
    'a resource naming a title attribute' => fn (): array => [new Article('Draft'), '/admin/articles/%s/edit', 'Edit Draft', 'Edit Article'],
    'a resource naming none' => fn (): array => [new VettedBinder(), '/edit-shapes/binders/sheeted/%s/edit', 'Edit Vetted Binder', 'Edit Vetted Binder'],
]);

it("ends the trail with the record's crumb, linked to its view page, else its edit page, else nowhere, then the page's own", function (object $record, string $uri, array $crumbs, array $links): void {
    $this->seed($record);
    $key = RecordKey::of($this->entityManager(), $record);

    $page = $this->visit(sprintf($uri, $key));

    expect($page->filter(SupportSelectors::BREADCRUMB)->each(static fn (Crawler $crumb): string => $crumb->text()))->toBe($crumbs)
        ->and($page->filter(SupportSelectors::LINKED_BREADCRUMB)->each(static fn (Crawler $crumb): ?string => $crumb->attr('href')))->toBe(array_map(static fn (string $link): string => sprintf($link, $key), $links));
})->with([
    'a resource with a view page' => fn (): array => [
        new OrderLine(order: 7, line: 2, note: 'First'),
        '/edit-shapes/banded-order-lines/%s/edit',
        ['Order Lines', 'First', 'Edit'],
        ['http://localhost/edit-shapes/banded-order-lines', 'http://localhost/edit-shapes/banded-order-lines/%s'],
    ],
    'a resource with an edit page' => fn (): array => [
        new Article('Draft'),
        '/admin/articles/%s/edit',
        ['Articles', 'Draft', 'Edit'],
        ['http://localhost/admin/articles', 'http://localhost/admin/articles/%s/edit'],
    ],
    'a resource with neither' => fn (): array => [
        new CompanionHerb('Mint', 'Mentha'),
        '/edit-shapes/variant-herbs/%s/actioned',
        ['Companion Herbs', 'Mint', 'Edit'],
        ['http://localhost/edit-shapes/variant-herbs'],
    ],
    'a resource naming no record title' => fn (): array => [
        new VettedBinder(),
        '/edit-shapes/binders/sheeted/%s/edit',
        ['Vetted Binders', 'Edit'],
        ['http://localhost/edit-shapes/binders/sheeted'],
    ],
]);

describe('the record sub-navigation', function (): void {
    it("draws the resource's record band beside the page, the current page active and each item's URL carrying the record key", function (object $record, string $resourcePath, string $label): void {
        $this->seed($record);
        $key = RecordKey::of($this->entityManager(), $record);

        $page = $this->visit("{$resourcePath}/{$key}/edit");

        expect($page->filter(Selectors::pageWithSubNavigation()))->toHaveCount(1)
            ->and($page->filter(Selectors::SUB_NAVIGATION . ' ' . Selectors::NAVIGATION_ITEM_LABEL)->each(static fn (Crawler $item): string => $item->text()))->toBe(["Edit Banded {$label}", "View Banded {$label}"])
            ->and($page->filter(Selectors::SUB_NAVIGATION . ' a')->each(static fn (Crawler $item): ?string => $item->attr('href')))->toBe(["{$resourcePath}/{$key}/edit", "{$resourcePath}/{$key}"])
            ->and($page)->toContainElement(Selectors::ACTIVE_SUB_NAVIGATION_ITEM, "Edit Banded {$label}");
    })->with([
        'scalar fields' => fn (): array => [new OrderLine(order: 7, line: 2, note: 'First'), '/edit-shapes/banded-order-lines', 'Order Line'],
        'an association plus a scalar' => fn (): array => [new InvoiceLine(new Invoice(7), line: 2, note: 'First'), '/edit-shapes/banded-invoice-lines', 'Invoice Line'],
    ]);

    it('draws none for a resource that declares none', function (): void {
        $this->seed($article = new Article('Draft'));

        $page = $this->visit("/admin/articles/{$article->id}/edit");

        expect($page->filter(Selectors::PAGE))->toHaveCount(1)
            ->and($page->filter(Selectors::pageWithSubNavigation()))->toHaveCount(0)
            ->and($page->filter(Selectors::SUB_NAVIGATION))->toHaveCount(0);
    });
});

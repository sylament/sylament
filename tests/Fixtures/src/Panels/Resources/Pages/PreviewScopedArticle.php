<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Tests\Fixtures\Panels\Resources\ScopedArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A custom record page: a `mount()` handing the trait its key, a crumb and a child template
 * reading the record, with header links minted through `getResourceUrl()`: the edit page with
 * the record left to the page, the listing, and the edit page of a record it names.
 */
#[AsLiveComponent]
final class PreviewScopedArticle extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ScopedArticleResource::class;

    protected static ?string $breadcrumb = 'Preview';

    protected ?string $template = '@Fixtures/panels/preview-scoped-article.html.twig';

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('editor')->url(fn (): string => $this->getResourceUrl('edit')),
            Action::make('listing')->url(fn (): string => $this->getResourceUrl('index')),
            Action::make('featured')->url(fn (): string => $this->getResourceUrl('edit', ['record' => 'featured'])),
        ];
    }
}

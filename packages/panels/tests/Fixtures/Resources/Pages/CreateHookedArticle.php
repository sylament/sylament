<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * The create page's counterpart to {@see EditHookedArticle} — the same
 * recording, over the create lifecycle, where the mutator runs *before*
 * `beforeCreate` rather than after the write hook the way the edit page's does.
 */
#[AsLiveComponent]
final class CreateHookedArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @var array<string>
     */
    #[LiveProp]
    public array $calls = [];

    protected function beforeFill(): void
    {
        $this->calls[] = 'beforeFill';
    }

    protected function afterFill(): void
    {
        $this->calls[] = 'afterFill';
    }

    protected function beforeValidate(): void
    {
        $this->calls[] = 'beforeValidate';
    }

    protected function afterValidate(): void
    {
        $this->calls[] = 'afterValidate';
    }

    protected function beforeCreate(): void
    {
        $this->calls[] = 'beforeCreate';
    }

    protected function afterCreate(): void
    {
        $this->calls[] = 'afterCreate';
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->calls[] = 'mutateCreate';

        $title = $data['title'] ?? null;

        return [...$data, 'title' => is_string($title) ? mb_strtoupper($title) : $title];
    }
}

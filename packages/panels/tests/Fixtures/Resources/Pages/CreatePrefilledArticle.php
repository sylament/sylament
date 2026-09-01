<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Replaces the whole fill rather than hooking around it — what the `fillForm()`
 * seam exists for, and the shape a create page reaching a prefilled form takes.
 */
#[AsLiveComponent]
final class CreatePrefilledArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function fillForm(): void
    {
        $this->getSchema('form')->fill(['title' => 'Prefilled', 'slug' => 'prefilled']);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An article's edit page that turns the unsaved-changes alert off for itself, whatever its
 * panel says; no resource routes it, so a test mounts it with the id of a panel that turns
 * the alerts on.
 */
#[AsLiveComponent]
final class UnguardedEditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected ?bool $hasUnsavedDataChangesAlert = false;
}

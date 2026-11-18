<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Tests\Fixtures\Panels\Resources\ArticleResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An article's edit page that turns the unsaved-changes alert on for itself, whatever its
 * panel says; no resource routes it, so a test mounts it as a live component, where it
 * answers for the default panel, which guards nothing.
 */
#[AsLiveComponent]
final class GuardedEditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected ?bool $hasUnsavedDataChangesAlert = true;
}

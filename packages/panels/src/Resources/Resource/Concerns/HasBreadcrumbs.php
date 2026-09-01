<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Resource\Concerns;

trait HasBreadcrumbs
{
    protected static ?string $breadcrumb = null;

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? $this->getTitleCasePluralRecordLabel();
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages\Concerns;

use Sylament\Support\Enums\Width;

trait HasMaxWidth
{
    protected Width|string|null $maxWidth = null;

    public function getMaxWidth(): Width|string|null
    {
        return $this->maxWidth;
    }
}

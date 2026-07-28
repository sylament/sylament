<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Support\Concerns\EvaluatesClosures;

class CanOpenUrlFixture
{
    use CanOpenUrl;
    use EvaluatesClosures;

    /**
     * Stands in for `CanOpenModal::hasModal()`, which `getUrl()`'s modal
     * precedence guard requires the composing class to supply.
     */
    public function hasModal(): ?bool
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Actions\Contracts\InteractsWithActions;
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

    /**
     * Stands in for `BelongsToLiveComponent::getLiveComponentOrNull()`, which
     * `getUrl()`'s host-hook fallthrough requires the composing class to supply.
     * Always hostless: the hook is typed against `Action`, so only a real
     * `Action` can exercise the fallthrough.
     */
    public function getLiveComponentOrNull(): ?InteractsWithActions
    {
        return null;
    }
}

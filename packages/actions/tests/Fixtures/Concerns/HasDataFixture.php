<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Concerns\HasData;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Support\Concerns\EvaluatesClosures;

/**
 * Bare fixture for HasData: composes only EvaluatesClosures + HasData. HasData::getRawData()
 * assumes getLiveComponentOrNull()/getNestingIndex() are present on the composing class (as they
 * are on Action) without importing BelongsToLiveComponent, so this fixture stubs both directly —
 * always returning null — since it cannot construct a real InteractsWithActions host.
 */
class HasDataFixture
{
    use EvaluatesClosures;
    use HasData;

    public function getLiveComponentOrNull(): ?InteractsWithActions
    {
        return null;
    }

    public function getNestingIndex(): ?int
    {
        return null;
    }
}

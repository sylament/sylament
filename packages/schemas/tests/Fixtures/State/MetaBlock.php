<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\State;

/**
 * The seo property is deliberately non-nullable: an inner branch over it can
 * only survive the branch pass by being short-circuited under a nulled outer
 * branch, which is what pins the pass's path-depth ordering.
 */
final class MetaBlock
{
    public SeoBlock $seo;

    public ?string $note = null;

    public function __construct()
    {
        $this->seo = new SeoBlock();
    }
}

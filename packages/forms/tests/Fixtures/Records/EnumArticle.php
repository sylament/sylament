<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

use Sylament\Forms\Tests\Fixtures\Components\Concerns\OptionStatus;

/**
 * The record whose properties are typed as the enum itself: `status` is the
 * single-value target an enum-backed select fills from and writes back, and
 * `tags` the list a `multiple()` one maps onto. Both are nullable/empty by
 * default so requirement derivation stays out of the round-trip.
 */
final class EnumArticle
{
    public ?OptionStatus $status = null;

    /**
     * @var list<OptionStatus>
     */
    public array $tags = [];
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

/**
 * Not a record: the watermark form's three properties, writable, which a form
 * mapped onto it would fill.
 */
final class WatermarkDraft
{
    public ?string $mill = null;

    public ?string $pattern = null;

    public ?string $note = null;
}

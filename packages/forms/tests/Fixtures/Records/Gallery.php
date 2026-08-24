<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Records;

/**
 * The record a file upload binds to: `coverImage` is the single-file `?string` column, `gallery`
 * the multiple-file JSON list, and `uninitializedCover` the typed-but-unset property whose read
 * raises an `UninitializedPropertyException` — the arm `getOriginalFilePaths()` has to fail closed
 * on rather than propagate.
 */
final class Gallery
{
    public ?string $coverImage = null;

    /**
     * @var array<array-key, mixed>|null
     */
    public ?array $gallery = null;

    public string $uninitializedCover;
}

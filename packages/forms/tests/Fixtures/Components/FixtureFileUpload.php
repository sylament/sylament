<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Components;

use Sylament\Forms\Components\BaseFileUpload;

/**
 * The concrete subclass `BaseFileUpload`'s own unit suite instantiates: the base is abstract
 * because it renders nothing, so everything below `FileUpload`'s FilePond surface is exercised
 * here instead — and a second subclass is what proves those methods live on the base rather than
 * on the shipped field.
 */
final class FixtureFileUpload extends BaseFileUpload
{
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\SimplePage;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A simple page that spells its content width as a `Width` token string and heads itself
 * with nothing but the logo.
 */
#[AsLiveComponent]
final class TokenWidthSimplePage extends SimplePage
{
    protected ?string $heading = '';

    protected Width|string|null $maxContentWidth = 'md';
}

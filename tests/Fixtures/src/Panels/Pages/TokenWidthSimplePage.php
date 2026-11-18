<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\SimplePage;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A simple page that names its width twice, a `Width` token spelled as a string for its
 * card and a narrower content width beneath it, and heads itself with nothing but the
 * logo.
 */
#[AsLiveComponent]
final class TokenWidthSimplePage extends SimplePage
{
    protected ?string $heading = '';

    protected Width|string|null $maxWidth = 'md';

    protected Width|string|null $maxContentWidth = Width::Small;
}

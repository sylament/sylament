<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\SimplePage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A simple page that turns the card's furniture off: no logo, no heading, no subheading
 * and no top strip.
 */
#[AsLiveComponent]
final class BareSimplePage extends SimplePage
{
    protected ?string $heading = '';

    protected bool $hasTopbar = false;

    public function hasLogo(): bool
    {
        return false;
    }
}

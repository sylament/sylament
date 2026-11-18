<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page naming a shell layout of its own, which no panel page may.
 */
#[AsLiveComponent]
final class CustomShellPage extends Page
{
    protected static ?string $layout = 'App:MarketingLayout';
}

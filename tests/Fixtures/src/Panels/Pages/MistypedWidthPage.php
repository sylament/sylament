<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page whose layout data names its width as a pixel count, which the controller refuses.
 */
#[AsLiveComponent]
final class MistypedWidthPage extends Page
{
    protected static ?string $slug = 'mistyped-width';

    protected static bool $shouldRegisterNavigation = false;

    public function getLayoutData(): array
    {
        return ['maxContentWidth' => 1280];
    }
}

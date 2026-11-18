<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page naming a layout neither shipped one is, which the controller refuses.
 */
#[AsLiveComponent]
final class ThirdLayoutPage extends Page
{
    protected static ?string $layout = 'App:KioskLayout';

    protected static ?string $slug = 'kiosk';

    protected static bool $shouldRegisterNavigation = false;
}

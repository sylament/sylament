<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class OptedOutNavigationPage extends Page
{
    /**
     * Redeclared rather than assigned through `static::`: writing the base class's
     * property would opt every page out of navigation.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Opted Out';
}

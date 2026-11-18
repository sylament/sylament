<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use BackedEnum;
use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A page in the `Library` navigation group whose item draws one icon and another while it is the page shown.
 */
#[AsLiveComponent]
final class ActiveIconShelfPage extends Page
{
    protected static ?string $slug = 'shelf';

    protected static ?string $navigationGroup = 'Library';

    protected static string|BackedEnum|null $navigationIcon = 'support:star';

    protected static string|BackedEnum|null $activeNavigationIcon = 'support:heart';
}

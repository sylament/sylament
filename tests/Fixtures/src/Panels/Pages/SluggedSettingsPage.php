<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A plain page at a slug of its own, with no navigation sort or group.
 */
#[AsLiveComponent]
final class SluggedSettingsPage extends Page
{
    protected static ?string $slug = 'settings';
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent]
final class SettingsPage extends Page
{
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Content';
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Pages;

use Sylament\Panels\Pages\Page;
use Sylament\Panels\Pages\SimplePage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * An ordinary page that stays out of navigation, renders in the simple shell, whose layout
 * data turns the shell's topbar off, and heads itself with nothing.
 */
#[AsLiveComponent]
final class StandalonePage extends Page
{
    protected static ?string $layout = SimplePage::LAYOUT;

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $heading = '';

    public function getLayoutData(): array
    {
        return ['hasTopbar' => false];
    }
}

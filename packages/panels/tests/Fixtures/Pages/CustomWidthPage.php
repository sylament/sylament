<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Pages\Page;
use Sylament\Support\Enums\Width;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Twig\Markup;

/**
 * The raw-string width arm, and the headingless page whose header exists only
 * through the header guard's `headerActions is not empty` term.
 */
#[AsLiveComponent]
final class CustomWidthPage extends Page
{
    protected static ?string $slug = 'wide';

    protected Width|string|null $maxContentWidth = 'max-w-[42rem]';

    public function getHeading(): string|Markup|null
    {
        return null;
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [Action::make('wide')->label('Wide')->action(fn (): null => null)];
    }
}

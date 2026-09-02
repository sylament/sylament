<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use Sylament\Panels\Pages\Concerns\HasMaxWidth;
use Sylament\Panels\Pages\Concerns\HasTopbar;

/**
 * Filament's `SimplePage` extends `BasePage` and is routed only by the auth and
 * tenancy arms; this one extends `Page` so `pages()` routes it, and hides from
 * navigation by default — simple-page.md.
 */
abstract class SimplePage extends Page
{
    use HasMaxWidth;
    use HasTopbar;

    public const string LAYOUT = 'SylamentPanels:SimpleLayout';

    protected static ?string $layout = self::LAYOUT;

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $template = '@SylamentPanels/partials/SimplePage/index.html.twig';

    public function hasLogo(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLayoutData(): array
    {
        return [
            'hasTopbar' => $this->hasTopbar(),
            'maxContentWidth' => $this->getMaxWidth() ?? $this->getMaxContentWidth(),
        ];
    }
}

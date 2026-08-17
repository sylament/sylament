<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Support\Contracts\HasIcon;
use Twig\Markup;

use function Sylament\Support\blank;

trait HasIcons
{
    /**
     * @var array<string|int, string|BackedEnum|Markup|null>|Closure|null
     */
    protected array|Closure|null $icons = null;

    /**
     * @var ?array<string|int, string|BackedEnum|Markup|null>
     */
    protected ?array $cachedIcons = null;

    protected bool $hasCachedIcons = false;

    /**
     * @param array<string|int, string|BackedEnum|Markup|null>|Closure|null $icons
     */
    public function icons(array|Closure|null $icons): static
    {
        $this->icons = $icons;

        $this->cachedIcons = null;
        $this->hasCachedIcons = false;

        return $this;
    }

    public function getIcon(string|int $value): string|BackedEnum|Markup|null
    {
        return $this->getIcons()[$value] ?? null;
    }

    /**
     * @return array<string|int, string|BackedEnum|Markup|null>
     */
    public function getIcons(): array
    {
        if ($this->hasCachedIcons) {
            return $this->cachedIcons ?? [];
        }

        $icons = $this->evaluate($this->icons);

        if (blank($icons) && ($enum = $this->getEnum()) !== null && is_a($enum, HasIcon::class, allow_string: true)) {
            $icons = [];

            foreach ($enum::cases() as $case) {
                $icons[$case instanceof BackedEnum ? $case->value : $case->name] = $case->getIcon();
            }
        }

        $this->cachedIcons = $icons ?? [];
        $this->hasCachedIcons = true;

        return $this->cachedIcons;
    }
}

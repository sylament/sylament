<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Support\Contracts\HasColor;

use function Sylament\Support\blank;

trait HasColors
{
    /**
     * @var array<string|int, string|array<int|string, string|int>|null>|Closure|null
     */
    protected array|Closure|null $colors = null;

    /**
     * @var ?array<string|int, string|array<int|string, string|int>|null>
     */
    protected ?array $cachedColors = null;

    protected bool $hasCachedColors = false;

    /**
     * @param array<string|int, string|array<int|string, string|int>|null>|Closure|null $colors
     */
    public function colors(array|Closure|null $colors): static
    {
        $this->colors = $colors;

        $this->cachedColors = null;
        $this->hasCachedColors = false;

        return $this;
    }

    /**
     * @return string|array<int|string, string|int>|null
     */
    public function getColor(string|int $value): string|array|null
    {
        return $this->getColors()[$value] ?? null;
    }

    /**
     * @return array<string|int, string|array<int|string, string|int>|null>
     */
    public function getColors(): array
    {
        if ($this->hasCachedColors) {
            return $this->cachedColors ?? [];
        }

        $colors = $this->evaluate($this->colors);

        if (blank($colors) && ($enum = $this->getEnum()) !== null && is_a($enum, HasColor::class, allow_string: true)) {
            $colors = [];

            foreach ($enum::cases() as $case) {
                $colors[$case instanceof BackedEnum ? $case->value : $case->name] = $case->getColor();
            }
        }

        $this->cachedColors = $colors ?? [];
        $this->hasCachedColors = true;

        return $this->cachedColors;
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Support\Contracts\HasDescription;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

trait HasDescriptions
{
    /**
     * @var array<string|int, string|Markup>|Closure
     */
    protected array|Closure $descriptions = [];

    /**
     * @var ?array<string|int, string|Markup>
     */
    protected ?array $cachedDescriptions = null;

    protected bool $hasCachedDescriptions = false;

    /**
     * @param array<string|int, string|Markup>|Closure $descriptions
     */
    public function descriptions(array|Closure $descriptions): static
    {
        $this->descriptions = $descriptions;

        $this->cachedDescriptions = null;
        $this->hasCachedDescriptions = false;

        return $this;
    }

    public function hasDescription(string|int $value): bool
    {
        return array_key_exists($value, $this->getDescriptions());
    }

    public function getDescription(string|int $value): string|Markup|null
    {
        return $this->getDescriptions()[$value] ?? null;
    }

    /**
     * @return array<string|int, string|Markup>
     */
    public function getDescriptions(): array
    {
        if ($this->hasCachedDescriptions) {
            return $this->cachedDescriptions ?? [];
        }

        $descriptions = $this->evaluate($this->descriptions);

        if (blank($descriptions) && ($enum = $this->getEnum()) !== null && is_a($enum, HasDescription::class, allow_string: true)) {
            $descriptions = [];

            foreach ($enum::cases() as $case) {
                $description = $case->getDescription();

                if (filled($description)) {
                    $descriptions[$case instanceof BackedEnum ? $case->value : $case->name] = $description;
                }
            }
        }

        $this->cachedDescriptions = $descriptions;
        $this->hasCachedDescriptions = true;

        return $this->cachedDescriptions;
    }
}

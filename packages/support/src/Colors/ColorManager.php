<?php

declare(strict_types=1);

namespace Sylament\Support\Colors;

use Closure;
use LogicException;
use Psr\Container\ContainerInterface;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Support\Colors\Contracts\HasDefaultGrayColor;
use Sylament\Support\Concerns\EvaluatesClosures;

use function Sylament\Support\blank;

class ColorManager
{
    use EvaluatesClosures;

    /**
     * @var array<string, array<int, string>>
     */
    public const array DEFAULT_COLORS = [
        'danger' => Color::Red,
        'gray' => Color::Zinc,
        'info' => Color::Sky,
        'primary' => Color::Blue,
        'success' => Color::Green,
        'warning' => Color::Amber,
    ];

    /**
     * @var array<array<string, array<int, string>|string>|Closure>
     */
    protected array $colors = [];

    /**
     * @var array<string, array<int, string>>|null
     */
    protected ?array $cachedColors = null;

    /**
     * @var array<string, array<string, list<string>>>
     */
    protected array $componentClasses = [];

    /**
     * @var array<string, array<string, list<string>>>
     */
    protected array $componentCustomStyles = [];

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
    }

    /**
     * @param array<string, array<int, string>|string>|Closure $colors
     */
    public function register(array|Closure $colors): static
    {
        $this->colors[] = $colors;

        return $this;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getColors(): array
    {
        if ($this->cachedColors !== null) {
            return $this->cachedColors;
        }

        array_unshift($this->colors, static::DEFAULT_COLORS);

        $cachedColors = [];

        foreach ($this->colors as $colors) {
            foreach ($this->evaluate($colors) as $name => $color) {
                $cachedColors[$name] = is_string($color)
                    ? Color::generatePalette($color)
                    : array_map(Color::convertToOklch(...), $color);
            }
        }

        return $this->cachedColors = $cachedColors;
    }

    /**
     * @return array<int, string>|null
     */
    public function getColor(string $color): ?array
    {
        return $this->getColors()[$color] ?? null;
    }

    /**
     * @param class-string<HasColor>|HasColor $component
     * @return list<string>
     */
    public function getComponentClasses(string|HasColor $component, ?string $color): array
    {
        if (blank($color)) {
            return [];
        }

        $componentKey = is_string($component) ? $component : serialize($component);

        if (isset($this->componentClasses[$componentKey][$color])) {
            return $this->componentClasses[$componentKey][$color];
        }

        $component = $this->resolveComponent($component);

        if ($color === 'gray' && $component instanceof HasDefaultGrayColor) {
            return $this->componentClasses[$componentKey][$color] = [];
        }

        $classes = ['sy-color', 'sy-color-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $color)];

        $resolvedColor = $this->getColor($color);

        if ($resolvedColor === null || $resolvedColor === []) {
            return $this->componentClasses[$componentKey][$color] = $classes;
        }

        $map = $component->getColorMap($resolvedColor);

        return $this->componentClasses[$componentKey][$color] = [
            ...$classes,
            ...array_map(
                fn (int $shade, string $key): string => match ($key) {
                    'bg' => "sy-bg-color-{$shade}",
                    'dark:bg' => "dark:sy-bg-color-{$shade}",
                    'dark:hover:bg' => "dark:hover:sy-bg-color-{$shade}",
                    'dark:hover:text' => "dark:hover:sy-text-color-{$shade}",
                    'dark:text' => "dark:sy-text-color-{$shade}",
                    'hover:bg' => "hover:sy-bg-color-{$shade}",
                    'hover:text' => "hover:sy-text-color-{$shade}",
                    'text' => "sy-text-color-{$shade}",
                    default => throw new LogicException("Invalid color mapping key [{$key}]."),
                },
                array_values($map),
                array_keys($map),
            ),
        ];
    }

    /**
     * @param class-string<HasColor>|HasColor $component
     * @param array<int, string> $color
     * @return list<string>
     */
    public function getComponentCustomStyles(string|HasColor $component, array $color): array
    {
        $componentKey = is_string($component) ? $component : serialize($component);
        $colorKey = serialize($color);

        if (isset($this->componentCustomStyles[$componentKey][$colorKey])) {
            return $this->componentCustomStyles[$componentKey][$colorKey];
        }

        $map = $this->resolveComponent($component)->getColorMap($color);

        return $this->componentCustomStyles[$componentKey][$colorKey] = [
            ...array_map(
                fn (string $shadeColor, int $shade): string => "--sy-color-{$shade}: " . $this->sanitizeCssColor($shadeColor),
                array_values($color),
                array_keys($color),
            ),
            ...array_map(
                fn (int $shade, string $key): string => '--sy-' . str_replace(':', '-', $key) . ': ' . ($shade === 0 ? 'oklch(1 0 0)' : "var(--sy-color-{$shade})"),
                array_values($map),
                array_keys($map),
            ),
        ];
    }

    /**
     * @param class-string<HasColor>|HasColor $component
     */
    protected function resolveComponent(string|HasColor $component): HasColor
    {
        if (!is_string($component)) {
            return $component;
        }

        $resolved = $this->container->get($component);

        if (!$resolved instanceof HasColor) {
            throw new LogicException(sprintf('The color component [%s] must resolve to a service implementing [%s].', $component, HasColor::class));
        }

        return $resolved;
    }

    /**
     * Security: the value is interpolated into a style attribute, so anything that could
     * close the declaration or open a url() must never reach it.
     */
    protected function sanitizeCssColor(string $color): string
    {
        return preg_match('/^[a-zA-Z0-9\s.,%()#\/-]+$/', $color) === 1 ? $color : '';
    }
}

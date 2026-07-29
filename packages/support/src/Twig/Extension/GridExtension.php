<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Sylament\Support\Enums\GridDirection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

use function Sylament\Support\filled;

class GridExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('grid', $this->getGridAttributes(...)),
            new TwigFunction('gridColumn', $this->getGridColumnAttributes(...)),
        ];
    }

    /**
     * @param array<string, ?int>|int|null $columns
     * @return array<string, array<string>>
     */
    public function getGridAttributes(array|int|null $columns = [], GridDirection $direction = GridDirection::Row): array
    {
        if (!is_array($columns)) {
            $columns = ['lg' => $columns];
        }

        $columns = array_filter(array_filter($columns), filled(...), ARRAY_FILTER_USE_KEY);

        $columns['default'] ??= 1;

        return [
            'class' => $this->buildClasses($columns, $direction),
            'style' => $this->buildStyles($columns, $direction),
        ];
    }

    /**
     * @param array<string, ?int> $columns
     * @return array<string>
     */
    private function buildClasses(array $columns = [], GridDirection $direction = GridDirection::Row): array
    {
        $classes = [
            'sy-grid',
        ];

        if ($direction === GridDirection::Column) {
            $classes[] = 'sy-grid-direction-col';
        }

        foreach (array_keys($columns) as $breakpoint) {
            if ($breakpoint === 'default') {
                $classes[] = 'sy-grid-cols';

                continue;
            }

            $classes[] = "{$breakpoint}:sy-grid-cols";
        }

        return $classes;
    }

    /**
     * @param array<string, ?int> $columns
     * @return array<string>
     */
    private function buildStyles(array $columns = [], GridDirection $direction = GridDirection::Row): array
    {
        $styles = [];

        foreach ($columns as $breakpoint => $cols) {
            $styles[] = match ($direction) {
                GridDirection::Row => '--cols-' . $this->sanitizeBreakpoint($breakpoint) . ": repeat({$cols}, minmax(0, 1fr))",
                GridDirection::Column => '--cols-' . $this->sanitizeBreakpoint($breakpoint) . ": {$cols}",
            };
        }

        return $styles;
    }

    /**
     * @param array<string, int|string|null>|int|string|null $span
     * @param array<string, int|string|null>|int|string|null $start
     * @param array<string, ?int>|int|string|null $order
     * @return array<string, array<string>>
     */
    public function getGridColumnAttributes(
        array|int|string|null $span = [],
        array|int|string|null $start = [],
        array|int|string|null $order = [],
        bool $isHidden = false,
    ): array {
        $span = $this->normalizeGridColumnBreakpoints($span);
        $start = $this->normalizeGridColumnBreakpoints($start);
        $order = $this->normalizeGridColumnBreakpoints($order);

        return [
            'class' => $this->buildGridColumnClasses($span, $start, $order, $isHidden),
            'style' => $this->buildGridColumnStyles($span, $start, $order),
        ];
    }

    /**
     * @param array<string, int|string|null>|int|string|null $value
     * @return array<string, int|string>
     */
    private function normalizeGridColumnBreakpoints(array|int|string|null $value): array
    {
        if (!is_array($value)) {
            $value = [
                'lg' => $value,
            ];
        }

        return array_filter(array_filter($value), filled(...), ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array<string, int|string> $span
     * @param array<string, int|string> $start
     * @param array<string, int|string> $order
     * @return array<string>
     */
    private function buildGridColumnClasses(array $span, array $start, array $order, bool $isHidden): array
    {
        $classes = [
            'sy-grid-col',
        ];

        if ($isHidden || ($span['default'] ?? null) === 'hidden') {
            $classes[] = 'sy-hidden';
        }

        foreach (array_keys($span) as $breakpoint) {
            if ($breakpoint === 'default') {
                continue;
            }

            $classes[] = "{$breakpoint}:sy-grid-col-span";
        }

        foreach (array_keys($start) as $breakpoint) {
            if ($breakpoint === 'default') {
                $classes[] = 'sy-grid-col-start';

                continue;
            }

            $classes[] = "{$breakpoint}:sy-grid-col-start";
        }

        foreach (array_keys($order) as $breakpoint) {
            if ($breakpoint === 'default') {
                $classes[] = 'sy-grid-col-order';

                continue;
            }

            $classes[] = "{$breakpoint}:sy-grid-col-order";
        }

        return $classes;
    }

    /**
     * @param array<string, int|string> $span
     * @param array<string, int|string> $start
     * @param array<string, int|string> $order
     * @return array<string>
     */
    private function buildGridColumnStyles(array $span, array $start, array $order): array
    {
        $styles = [];

        foreach ($span as $breakpoint => $value) {
            $styles[] = '--col-span-' . $this->sanitizeBreakpoint($breakpoint) . ': ' . match ($value) {
                'full' => '1 / -1',
                default => "span {$value} / span {$value}",
            };
        }

        foreach ($start as $breakpoint => $value) {
            $styles[] = '--col-start-' . $this->sanitizeBreakpoint($breakpoint) . ": {$value}";
        }

        foreach ($order as $breakpoint => $value) {
            $styles[] = '--col-order-' . $this->sanitizeBreakpoint($breakpoint) . ": {$value}";
        }

        return $styles;
    }

    private function sanitizeBreakpoint(int|string $breakpoint): string
    {
        return str_replace(['@', '!'], ['c', 'n'], (string) $breakpoint);
    }
}

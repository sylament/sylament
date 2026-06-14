<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Sylament\Support\Enum\GridDirection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GridExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('grid', $this->getGridAttributes(...)),
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

        $columns = array_filter($columns);

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
                GridDirection::Row => '--cols-' . str_replace('!', 'n', str_replace('@', 'c', $breakpoint)) . ": repeat({$cols}, minmax(0, 1fr))",
                GridDirection::Column => '--cols-' . str_replace('!', 'n', str_replace('@', 'c', $breakpoint)) . ": {$cols}",
            };
        }

        return $styles;
    }
}

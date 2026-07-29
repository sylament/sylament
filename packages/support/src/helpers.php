<?php

declare(strict_types=1);

namespace Sylament\Support;

use BackedEnum;
use Countable;
use Stringable;
use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Markup;

/**
 * @phpstan-assert-if-false !null $value
 */
function blank(mixed $value): bool
{
    if ($value === null) {
        return true;
    }

    if (is_string($value)) {
        return mb_trim($value) === '';
    }

    if (is_numeric($value) || is_bool($value)) {
        return false;
    }

    if ($value instanceof Markup) {
        return mb_trim((string) $value) === '';
    }

    if ($value instanceof Countable) {
        return count($value) === 0;
    }

    if ($value instanceof Stringable) {
        return mb_trim((string) $value) === '';
    }

    return $value === [];
}

/**
 * @phpstan-assert-if-true !null $value
 */
function filled(mixed $value): bool
{
    return !blank($value);
}

/**
 * @template TValue
 *
 * @param array<TValue>|TValue|null $value
 *
 * @return array<TValue>
 */
function wrap(mixed $value): array
{
    if ($value === null) {
        return [];
    }

    return is_array($value) ? $value : [$value];
}

/**
 * @param string|array<string>|null $alias
 */
function generate_icon_html(
    string|BackedEnum|Markup|null $icon,
    string|array|null $alias = null,
    ?ComponentAttributeBag $attributes = null,
    ?IconSize $size = null,
): ?Markup {
    if (filled($alias)) {
        $icon = IconManager::getInstance()->resolve($alias) ?: $icon;
    }

    if (blank($icon)) {
        return null;
    }

    $size ??= IconSize::Medium;

    $attributes = ($attributes ?? new ComponentAttributeBag())->merge([
        'class' => ['sy', 'sy-icon', "sy-size-{$size->value}"],
    ]);

    if ($icon instanceof Markup) {
        return new Markup("<span {$attributes->toHtml()}>{$icon}</span>", 'UTF-8');
    }

    if (is_string($icon) && str_contains($icon, '/')) {
        $attributeValues = $attributes->getAttributes();

        // An image path carries no accessible name of its own, so a screen reader
        // announces the filename. Default it to decorative unless the caller named it.
        if (
            blank($attributeValues['alt'] ?? null)
            && blank($attributeValues['aria-label'] ?? null)
            && blank($attributeValues['aria-labelledby'] ?? null)
        ) {
            $attributes = $attributes->merge(['alt' => '']);
        }

        $src = htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return new Markup("<img src=\"{$src}\" {$attributes->toHtml()} />", 'UTF-8');
    }

    if ($icon instanceof ScalableIcon) {
        $icon = $icon->getIconForSize($size);
    } elseif ($icon instanceof BackedEnum) {
        $icon = (string) $icon->value;
    }

    return new Markup(IconManager::getInstance()->renderIcon($icon, $attributes->toScalarAttributes()), 'UTF-8');
}

<?php

declare(strict_types=1);

namespace Sylament\Support;

use BackedEnum;
use Countable;
use Stringable;
use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enum\IconSize;
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
 * Port of Laravel's Arr::wrap(): null becomes an empty array, an array passes
 * through unchanged, and any other value becomes a single-element array. Used
 * where a closure-evaluated value must be iterated as a collection without a
 * `null`/single-item return silently emptying it or fataling the loop.
 *
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
 * Generates an icon's HTML: a resolvable alias overrides the given icon, a
 * `Markup` value renders raw inside a span wrapper, a string containing "/"
 * renders as an image, and anything else renders as an ux-icons icon name
 * through the IconManager, with `sy`, `sy-icon`, and `sy-size-*` classes
 * applied (the bare `sy` marker scopes the preflight on standalone icon roots,
 * per styling.md rule 2).
 *
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

        // A custom image-path icon carries no intrinsic accessible name, so it would be
        // announced by its filename. Default it to decorative (`alt=""`) unless the caller
        // named it, matching the `aria-hidden` that ux-icons adds to rendered SVG icons.
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

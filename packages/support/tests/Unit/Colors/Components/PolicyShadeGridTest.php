<?php

declare(strict_types=1);

namespace Sylament\Support\Tests\Unit\Colors\Components;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\BadgeComponent;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\DropdownComponent\ItemComponent;
use Sylament\Support\Colors\Components\IconButtonComponent;
use Sylament\Support\Colors\Components\LinkComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
use Sylament\Support\Colors\Components\ToggleComponent;
use Sylament\Support\Colors\Contracts\HasColor;
use UnexpectedValueException;

use function dirname;
use function Sylament\Support\Tests\colorManagerWith;

/**
 * A shade a policy resolves becomes a class name, and a class name with no rule fails in
 * silence: the slot variable is never set, the declaration reading it is invalid at
 * computed-value time, and the element renders uncoloured with nothing in the console. This
 * ties the shades the policies can emit to the shades `utilities.css` actually defines.
 */
dataset('adversarial palettes', function (): iterable {
    $shades = array_keys(ColorManager::DEFAULT_COLORS['gray']);

    yield 'uniformly dark' => [array_fill_keys($shades, 'oklch(0.15 0.05 260)')];

    yield 'pale but for the darkest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.99 0.01 90)'), [950 => 'oklch(0.2 0.05 90)']),
    ];

    yield 'dark but for the lightest shade' => [
        array_replace(array_fill_keys($shades, 'oklch(0.15 0.05 260)'), [50 => 'oklch(0.99 0 0)']),
    ];

    foreach (ColorManager::DEFAULT_COLORS as $name => $palette) {
        yield $name => [$palette];
    }
});

/**
 * The coloured rule each policy paints, as the nested selector path that reaches it: every step is
 * searched inside the block the step before it opened, so a selector spelled the same under two
 * parents still resolves to one rule. `button.css` needs that — `&.sy-color` introduces both the
 * outlined arm and the checked label arm — and its filled rule carries `:not(label)` because a
 * label button paints those same slots only while its input is checked. `ToggleComponent` is
 * absent because its stylesheet ships from forms, which pins it from its own suite; schemas'
 * `TextComponent` pins itself the same way, against a `components/text.css` that ships from here.
 *
 * @return array<string, array{class-string<HasColor>, string, list<string>}>
 */
function coloredRules(): array
{
    return [
        'badge' => [BadgeComponent::class, 'components/badge.css', ['&.sy-color']],
        'filled button' => [ButtonComponent::class, 'components/button.css', ['&:not(.sy-outlined).sy-color:not(label)']],
        'outlined button' => [OutlinedButtonComponent::class, 'components/button.css', ['&.sy-outlined', '&.sy-color']],
        'icon button' => [IconButtonComponent::class, 'components/icon-button.css', ['&.sy-color']],
        'link' => [LinkComponent::class, 'components/link.css', ['&.sy-color']],
        'dropdown item' => [ItemComponent::class, 'components/dropdown-item.css', ['&.sy-color']],
    ];
}

function stylesheet(string $name): string
{
    $path = dirname(__DIR__, 4) . "/assets/src/styles/{$name}";

    $css = file_get_contents($path);

    if ($css === false) {
        throw new UnexpectedValueException("Unable to read {$path}.");
    }

    return $css;
}

/**
 * @param class-string<HasColor> $policy
 * @param array<int, string> $palette
 * @return list<string> the emitted classes with the two carriers `getComponentClasses()` always
 *                      prepends removed, leaving only what the colour map resolved
 */
function shadeClasses(string $policy, array $palette): array
{
    $color = 'brand';
    $colors = colorManagerWith($color, $palette);

    return array_values(array_diff(
        $colors->getComponentClasses(new $policy($colors), $color),
        ['sy-color', "sy-color-{$color}"],
    ));
}

/**
 * The declarations the last selector of `$path` introduces, nested rules included — the filled
 * button reads two of its slots from a rule for the icon it contains, and the dropdown item reads
 * all four of its own from a rule for the label.
 *
 * @param list<string> $path
 */
function ruleDeclarations(string $css, array $path): string
{
    $block = $css;

    foreach ($path as $selector) {
        $marker = "{$selector} {";
        $occurrences = mb_substr_count($block, $marker);

        if ($occurrences !== 1) {
            throw new UnexpectedValueException("The selector `{$selector}` occurs {$occurrences} times in the block it is read from — this test needs exactly one rule to read.");
        }

        $start = mb_strpos($block, $marker);

        if ($start === false) {
            throw new UnexpectedValueException("The selector `{$selector}` is not in the stylesheet.");
        }

        $start += mb_strlen($marker);
        $depth = 1;
        $length = null;

        foreach (mb_str_split(mb_substr($block, $start)) as $offset => $character) {
            $depth += match ($character) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth === 0) {
                $length = $offset;

                break;
            }
        }

        if ($length === null) {
            throw new UnexpectedValueException("The rule for `{$selector}` is never closed.");
        }

        $block = mb_substr($block, $start, $length);
    }

    return $block;
}

/**
 * @param list<string> $path
 * @return list<string> the `--sy-` slot variables the rule reads, matched in the `bg-(--sy-bg)`
 *                      form Tailwind spells an arbitrary custom property with
 */
function slotVariablesRead(string $stylesheet, array $path): array
{
    preg_match_all(
        '/\(\s*(--sy-[a-z-]+)\s*\)/',
        ruleDeclarations(stylesheet($stylesheet), $path),
        $variables,
    );

    return array_values(array_unique($variables[1]));
}

/**
 * @param list<string> $classes
 * @return list<string> the `--sy-` slot variables those classes' rules declare
 */
function slotVariablesSetBy(array $classes): array
{
    $css = stylesheet('utilities.css');
    $variables = [];

    foreach ($classes as $class) {
        $selector = preg_quote(str_replace(':', '\:', $class), '/');

        if (preg_match('/^\.' . $selector . '\s*\{(?<declarations>[^}]*)\}/m', $css, $rule) === 1) {
            preg_match_all('/(--sy-[a-z-]+)\s*:/', $rule['declarations'], $declared);

            $variables = [...$variables, ...$declared[1]];
        }
    }

    return array_values(array_unique($variables));
}

it('emits only classes that utilities.css defines', function (array $palette): void {
    $css = stylesheet('utilities.css');

    $policies = [
        BadgeComponent::class,
        ButtonComponent::class,
        OutlinedButtonComponent::class,
        IconButtonComponent::class,
        LinkComponent::class,
        ToggleComponent::class,
        ItemComponent::class,
    ];

    foreach ($policies as $policy) {
        $shadeClasses = shadeClasses($policy, $palette);

        expect($shadeClasses)->not->toBeEmpty();

        foreach ($shadeClasses as $shadeClass) {
            expect($css)->toContain('.' . str_replace(':', '\:', $shadeClass) . ' {');
        }
    }
})->with('adversarial palettes');

/**
 * The class-defined check above passes for any grid shade under any slot key, so it says nothing
 * about whether the slots are the ones the component stylesheets read. Swap a policy's `text` slot
 * for `hover:text` and every emitted class still has a rule — it just sets `--sy-hover-text`,
 * leaving the `text-(--sy-text)` the component paints with unset. Hardcoding that declaration back
 * to `text-color-600` is the same failure from the other side: a slot resolved for nothing.
 *
 * The comparison is over the whole rule, nested declarations included, so a slot two rules inside
 * one component read still counts as read when only one of them keeps it — the filled button's
 * `--sy-text` and `--sy-dark-text`, painted on the button and again on the icon it contains, are
 * the only such pair today. Splitting the rule would need a per-declaration expectation the policy
 * cannot supply, since which of a component's elements paints with which slot is the stylesheet's
 * choice alone.
 */
it('sets exactly the slot variables the component stylesheets read', function (array $palette): void {
    foreach (coloredRules() as $description => [$policy, $stylesheet, $path]) {
        $read = slotVariablesRead($stylesheet, $path);
        $set = slotVariablesSetBy(shadeClasses($policy, $palette));
        $selector = implode(' → ', $path);

        sort($read);
        sort($set);

        expect($set)->toBe(
            $read,
            "The {$description} policy and the `{$selector}` rule in {$stylesheet} disagree on which "
            . '`--sy-` variables carry the resolved shades. A variable the rule reads and no class '
            . 'sets is a declaration the browser drops; a variable a class sets and no rule reads is '
            . 'a shade resolved for nothing.',
        );
    }
})->with('adversarial palettes');

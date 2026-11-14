<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Styles;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\ToggleComponent;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

/**
 * Reads the two halves of the toggle's colour slots: the classes support's policy emits and the `--sy-` variables
 * forms' `toggle.css` reads.
 */
final class ToggleSlots
{
    public static function supportStylesheet(string $name): string
    {
        return self::stylesheetAt(dirname(__DIR__, 3) . "/packages/support/assets/src/styles/{$name}");
    }

    /**
     * @param array<int, string> $palette
     *
     * @return list<string> the emitted classes with the two carriers `getComponentClasses()` always
     *                      prepends removed, leaving only what the colour map resolved
     */
    public static function shadeClasses(array $palette): array
    {
        $colors = new ColorManager(new ContainerBuilder());
        $colors->register(['brand' => $palette]);

        return array_values(array_diff(
            $colors->getComponentClasses(new ToggleComponent($colors), 'brand'),
            ['sy-color', 'sy-color-brand'],
        ));
    }

    /**
     * Grouped by state, because each state paints the whole policy on its own: the checked rules read
     * the slots directly and the unchecked ones read them through the wrapper aliases. Taking the
     * file as one set would let either state hardcode a shade and still be covered by the other.
     *
     * Only the slots a rule paints with count as reads — an alias declaration mentions the slot it
     * stands in for, and the `--sy-bg: initial` resets mention the slots they clear, neither of which
     * puts the shade on screen. So every custom-property declaration is dropped before reading.
     *
     * A rule is a selector run up to its braces, which is all the parsing this file needs: unlike the
     * component stylesheets support pins, the toggle's selectors combine the wrapper, the input and
     * the track rather than nesting.
     *
     * @return array<string, list<string>> state => the slot variables that state's rules read
     */
    public static function variablesReadByState(): array
    {
        $aliases = self::aliases();

        $states = ['checked' => [], 'unchecked' => []];

        preg_match_all('/(?<selector>[^{}]+)\{(?<declarations>[^{}]*)\}/', self::toggleStylesheet(), $rules, PREG_SET_ORDER);

        foreach ($rules as $rule) {
            $state = match (true) {
                str_contains($rule['selector'], ':not(:checked)') => 'unchecked',
                str_contains($rule['selector'], ':checked') => 'checked',
                default => null,
            };

            if ($state === null) {
                continue;
            }

            preg_match_all(
                '/\(\s*(--sy-[a-z-]+)\s*\)/',
                preg_replace('/--sy-[a-z-]+\s*:[^;]*;/', '', $rule['declarations']) ?? '',
                $variables,
            );

            $states[$state] = [
                ...$states[$state],
                ...array_map(static fn (string $variable): string => $aliases[$variable] ?? $variable, $variables[1]),
            ];
        }

        return array_map(
            static fn (array $variables): array => array_values(array_unique($variables)),
            $states,
        );
    }

    /**
     * @param list<string> $classes
     *
     * @return list<string> the `--sy-` slot variables those classes' rules declare
     */
    public static function variablesSetBy(array $classes): array
    {
        $css = self::supportStylesheet('utilities.css');
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

    /**
     * Comments are dropped rather than parsed: they sit inside the rules they explain, and a slot
     * named in prose is not a slot anything paints with.
     */
    private static function toggleStylesheet(): string
    {
        return preg_replace('#/\*.*?\*/#s', '', self::stylesheetAt(dirname(__DIR__, 3) . '/packages/forms/assets/src/styles/components/toggle.css')) ?? '';
    }

    /**
     * The off colour sits on the wrapper and the on colour on the track inside it, so the stylesheet
     * aliases the wrapper's three slots into names the track never redeclares. Reading an alias is
     * reading the slot it points at, which is why they are resolved here rather than counted as
     * variables of their own. One hop is all the file uses; a longer chain would leave the
     * intermediate name in the set and fail the comparison, which is the right way for it to break.
     *
     * @return array<string, string> alias => the slot variable it stands in for
     */
    private static function aliases(): array
    {
        preg_match_all('/(--sy-[a-z-]+)\s*:\s*var\(\s*(--sy-[a-z-]+)\s*\)/', self::toggleStylesheet(), $aliases, PREG_SET_ORDER);

        $aliased = [];

        foreach ($aliases as $alias) {
            $aliased[$alias[1]] = $alias[2];
        }

        return $aliased;
    }

    private static function stylesheetAt(string $path): string
    {
        $css = file_get_contents($path);

        if ($css === false) {
            throw new UnexpectedValueException("Unable to read {$path}.");
        }

        return $css;
    }
}

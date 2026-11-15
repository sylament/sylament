<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Styles;

use LogicException;
use Sylament\Schemas\Colors\Components\TextComponent;
use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Components\ButtonComponent;
use Sylament\Support\Colors\Components\OutlinedButtonComponent;
use Sylament\Support\Colors\Components\ToggleComponent;
use Sylament\Support\Colors\Contracts\HasColor;
use Sylament\Tests\Fixtures\Kernel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnexpectedValueException;

use function Symfony\Component\String\u;

/**
 * Reads both halves of every colour policy's slot contract: the shade classes the policy emits, with the `--sy-` slot
 * variables their `utilities.css` rules set, and the slot variables the coloured rules of its component stylesheet read.
 */
final class ColorPolicies
{
    /**
     * Where a policy's coloured rules sit away from its own package's `components/<kebab name>.css`.
     *
     * @var array<class-string<HasColor>, array{string, string}>
     */
    private const array STYLESHEETS = [
        // A variant of the button, painted by the `.sy-outlined` arm of the button's stylesheet.
        OutlinedButtonComponent::class => ['support', 'components/button.css'],
        // The policy ships from support, the toggle field that paints it from forms.
        ToggleComponent::class => ['forms', 'components/toggle.css'],
        // `.sy-sc-text` is styled from support, where its stylesheet was ported (`docs/progress/schemas/styles.md`).
        TextComponent::class => ['support', 'components/text.css'],
    ];

    /**
     * @var array<string, class-string<HasColor>>|null
     */
    private static ?array $policies = null;

    /**
     * The shipped palettes, and palettes with gaps that push a contrast search to the end of the scale and onto its
     * fallback.
     *
     * @return array<string, array<int, string>>
     */
    public static function palettes(): array
    {
        $shades = array_keys(ColorManager::DEFAULT_COLORS['gray']);

        return [
            'uniformly dark' => array_fill_keys($shades, 'oklch(0.15 0.05 260)'),
            'pale but for the darkest shade' => array_replace(array_fill_keys($shades, 'oklch(0.99 0.01 90)'), [950 => 'oklch(0.2 0.05 90)']),
            'dark but for the lightest shade' => array_replace(array_fill_keys($shades, 'oklch(0.15 0.05 260)'), [50 => 'oklch(0.99 0 0)']),
            ...ColorManager::DEFAULT_COLORS,
        ];
    }

    /**
     * Every policy the bundles register, fixtures left out, keyed by its package and its path under
     * `Colors/Components/`. Read once per process from a container the `Kernel` compiles and never boots, as
     * `ComponentRegistry` reads components, because Pest builds a dataset before any test exists.
     *
     * @return array<string, class-string<HasColor>>
     */
    public static function policies(): array
    {
        if (self::$policies !== null) {
            return self::$policies;
        }

        $environment = $_SERVER['APP_ENV'] ?? 'test';

        if (!is_string($environment)) {
            throw new LogicException('APP_ENV is not a string.');
        }

        $container = new Kernel($environment, true)->getCompiledContainer();

        if (!$container instanceof Container) {
            throw new LogicException(sprintf('The compiled container is a "%s", which lists no service ids.', get_debug_type($container)));
        }

        $policies = [];

        foreach ($container->getServiceIds() as $id) {
            if (!str_starts_with($id, 'Sylament\\') || str_starts_with($id, 'Sylament\\Tests\\')) {
                continue;
            }

            if (!class_exists($id) || !is_subclass_of($id, HasColor::class)) {
                continue;
            }

            ['package' => $package, 'path' => $path] = self::location($id);

            $policies["{$package} {$path}"] = $id;
        }

        ksort($policies);

        return self::$policies = $policies;
    }

    /**
     * The policies whose map resolves a shade for some palette; a policy whose map is empty for every palette states
     * its intent through CSS alone and has no slot for a stylesheet to read.
     *
     * @return array<string, class-string<HasColor>>
     */
    public static function slotResolvingPolicies(): array
    {
        return array_filter(
            self::policies(),
            static fn (string $policy): bool => array_any(self::palettes(), fn (array $palette): bool => self::shadeClasses($policy, $palette) !== []),
        );
    }

    /**
     * @param class-string<HasColor> $policy
     * @param array<int, string> $palette
     *
     * @return list<string> the emitted classes with the two carriers `getComponentClasses()` always prepends removed,
     *                      leaving only what the colour map resolved
     */
    public static function shadeClasses(string $policy, array $palette): array
    {
        $colors = new ColorManager(new ContainerBuilder());
        $colors->register(['brand' => $palette]);

        return array_values(array_diff(
            $colors->getComponentClasses(new $policy($colors), 'brand'),
            ['sy-color', 'sy-color-brand'],
        ));
    }

    /**
     * @param list<string> $classes
     *
     * @return list<string> the classes `utilities.css` has no rule for
     */
    public static function undefinedClasses(array $classes): array
    {
        $css = PaletteCopies::stylesheet('support', 'utilities.css');

        return array_values(array_filter(
            $classes,
            static fn (string $class): bool => !str_contains($css, '.' . str_replace(':', '\:', $class) . ' {'),
        ));
    }

    /**
     * @param list<string> $classes
     *
     * @return list<string> the `--sy-` slot variables those classes' `utilities.css` rules declare
     */
    public static function slotsSetBy(array $classes): array
    {
        $css = PaletteCopies::stylesheet('support', 'utilities.css');
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
     * The stylesheet holding the policy's coloured rules: an exception above, else the longest run of its path's
     * segments under `Colors/Components/` (`Component` dropped, kebab-cased, joined by hyphens) that names a file in its
     * own package's `components/`, so `DropdownComponent\ItemComponent` finds `dropdown-item.css` and
     * `TextColumnComponent\ItemComponent` its parent's `text-column.css`.
     *
     * @param class-string<HasColor> $policy
     *
     * @return array{string, string}|null the package and the path under its `assets/src/styles/`
     */
    public static function stylesheetOf(string $policy): ?array
    {
        if (isset(self::STYLESHEETS[$policy])) {
            return self::STYLESHEETS[$policy];
        }

        ['package' => $package, 'path' => $path] = self::location($policy);

        $segments = array_map(
            static fn (string $segment): string => (string) u($segment)->trimSuffix('Component')->kebab(),
            explode('\\', $path),
        );

        for ($length = count($segments); $length > 0; $length--) {
            $name = 'components/' . implode('-', array_slice($segments, 0, $length)) . '.css';

            if (is_file(PaletteCopies::stylesheetPath($package, $name))) {
                return [$package, $name];
            }
        }

        return null;
    }

    /**
     * Each group the policy's coloured rules form, with the slots it reads that `$set` lacks (`unset`: a declaration
     * the browser drops, painting the slot's unset default) and the slots in `$set` it never reads (`unread`: a shade
     * resolved for nothing). A group that agrees is left out, so an agreeing stylesheet yields `[]`.
     *
     * @param class-string<HasColor> $policy
     * @param list<string> $set
     *
     * @return array<string, array{unset: list<string>, unread: list<string>}>
     */
    public static function mismatches(string $policy, string $css, array $set): array
    {
        $mismatches = [];

        foreach (self::slotsReadByGroup($policy, $css) as $group => $read) {
            $unset = array_values(array_diff($read, $set));
            $unread = array_values(array_diff($set, $read));

            sort($unset);
            sort($unread);

            if ($unset !== [] || $unread !== []) {
                $mismatches[$group] = ['unset' => $unset, 'unread' => $unread];
            }
        }

        return $mismatches;
    }

    /**
     * Every component stylesheet whose coloured rules read a slot that no slot-resolving policy claims: the slots are
     * never set, so the stylesheet paints their unset defaults, and a policy whose map went empty is caught here
     * rather than skipped.
     *
     * @return list<string> package and path, as `forms components/toggle.css`
     */
    public static function unclaimedStylesheets(): array
    {
        $claimed = [];

        foreach (self::slotResolvingPolicies() as $policy) {
            $stylesheet = self::stylesheetOf($policy);

            if ($stylesheet !== null) {
                $claimed[] = implode(' ', $stylesheet);
            }
        }

        $unclaimed = [];

        foreach (glob(PaletteCopies::stylesheetPath('*', 'components/*.css')) ?: [] as $path) {
            $package = basename(dirname($path, 5));
            $name = 'components/' . basename($path);
            $read = [];

            foreach (self::colouredRules(PaletteCopies::stylesheet($package, $name)) as $rule) {
                $read = [...$read, ...$rule['reads']];
            }

            if ($read !== [] && !in_array("{$package} {$name}", $claimed, true)) {
                $unclaimed[] = "{$package} {$name}";
            }
        }

        return $unclaimed;
    }

    /**
     * The slots the policy's coloured rules read, grouped where one stylesheet paints the policy more than once:
     *
     * - the button's stylesheet paints two policies, the outlined one in the arm whose selector carries
     *   `.sy-outlined` and the filled one in every other coloured rule;
     * - the toggle paints its whole policy once per state, the checked rules reading the slots directly and the
     *   unchecked ones through the wrapper's aliases, so taking the file as one set would let either state hardcode a
     *   shade and still be covered by the other; a rule naming neither state paints in both.
     *
     * @param class-string<HasColor> $policy
     *
     * @return array<string, list<string>> group => the slot variables its rules read
     */
    private static function slotsReadByGroup(string $policy, string $css): array
    {
        $names = match ($policy) {
            ToggleComponent::class => ['checked', 'unchecked'],
            ButtonComponent::class => ['filled'],
            OutlinedButtonComponent::class => ['outlined'],
            default => ['coloured rules'],
        };

        $rules = self::colouredRules($css);
        $groups = [];

        foreach ($names as $name) {
            $read = [];

            foreach ($rules as $rule) {
                if (in_array($name, self::groupsOf($policy, $rule['selector']), true)) {
                    $read = [...$read, ...$rule['reads']];
                }
            }

            $groups[$name] = array_values(array_unique($read));
        }

        return $groups;
    }

    /**
     * @param class-string<HasColor> $policy
     *
     * @return list<string> the groups of `slotsReadByGroup()` a coloured rule with this selector paints
     */
    private static function groupsOf(string $policy, string $selector): array
    {
        return match ($policy) {
            ToggleComponent::class => match (true) {
                str_contains($selector, ':not(:checked)') => ['unchecked'],
                str_contains($selector, ':checked') => ['checked'],
                default => ['checked', 'unchecked'],
            },
            ButtonComponent::class => self::carries($selector, 'sy-outlined') ? [] : ['filled'],
            OutlinedButtonComponent::class => self::carries($selector, 'sy-outlined') ? ['outlined'] : [],
            default => ['coloured rules'],
        };
    }

    /**
     * Every rule whose selector, its nesting parents included, carries the `sy-color` swap class, with the slot
     * variables its own declarations read. Only a slot a rule paints with counts as a read: a custom-property
     * declaration mentions the slot it aliases or resets without putting a shade on screen, so it is dropped, and an
     * alias read (`--sy-off-bg: var(--sy-bg)`) is resolved one hop to its slot. A longer chain leaves the intermediate
     * name in the set and fails the comparison, which is the right way for it to break.
     *
     * @return list<array{selector: string, reads: list<string>}>
     */
    private static function colouredRules(string $css): array
    {
        $rules = self::rules($css);
        $aliases = [];

        foreach ($rules as $rule) {
            foreach ($rule['declarations'] as $declaration) {
                if (preg_match('/^(--sy-[a-z-]+)\s*:\s*var\(\s*(--sy-[a-z-]+)\s*\)$/', $declaration, $alias) === 1) {
                    $aliases[$alias[1]] = $alias[2];
                }
            }
        }

        $coloured = [];

        foreach ($rules as $rule) {
            if (!self::carries($rule['selector'], 'sy-color')) {
                continue;
            }

            $reads = [];

            foreach ($rule['declarations'] as $declaration) {
                if (str_starts_with($declaration, '--')) {
                    continue;
                }

                preg_match_all('/\(\s*(--sy-[a-z-]+)\s*\)/', $declaration, $variables);

                foreach ($variables[1] as $variable) {
                    $reads[] = $aliases[$variable] ?? $variable;
                }
            }

            $coloured[] = ['selector' => $rule['selector'], 'reads' => $reads];
        }

        return $coloured;
    }

    /**
     * Whether the selector carries the class outside a `:not()`, so `:not(.sy-color)` and `.sy-color-gray` do not.
     */
    private static function carries(string $selector, string $class): bool
    {
        $positive = preg_replace('/:not\((?:[^()]|\([^()]*\))*\)/', '', $selector)
            ?? throw new UnexpectedValueException("Unable to read the selector `{$selector}`.");

        return preg_match('/\.' . preg_quote($class, '/') . '(?![\w-])/', $positive) === 1;
    }

    /**
     * Each block of the stylesheet with its own declarations and its selector: its nesting parents' heads and its own,
     * joined by ` → `. Comments are dropped first, since a slot named in prose is not one anything paints with.
     *
     * @return list<array{selector: string, declarations: list<string>}>
     */
    private static function rules(string $css): array
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css)
            ?? throw new UnexpectedValueException("Unable to drop the stylesheet's comments.");

        $tokens = preg_split('/([{};])/', $css, flags: PREG_SPLIT_DELIM_CAPTURE)
            ?: throw new UnexpectedValueException('Unable to split the stylesheet into rules.');

        $heads = [];
        $declarations = [];
        $rules = [];
        $text = '';

        foreach ($tokens as $token) {
            if ($token === '{') {
                $heads[] = mb_trim($text);
                $declarations[] = [];
            } elseif ($token === ';' || $token === '}') {
                $declaration = mb_trim($text);

                if ($declaration !== '' && $declarations !== []) {
                    $declarations[array_key_last($declarations)][] = $declaration;
                }

                if ($token === '}') {
                    $rules[] = [
                        'selector' => implode(' → ', $heads),
                        'declarations' => array_pop($declarations) ?? throw new UnexpectedValueException('The stylesheet closes a block it never opened.'),
                    ];

                    array_pop($heads);
                }
            } else {
                $text = $token;

                continue;
            }

            $text = '';
        }

        if ($heads !== []) {
            throw new UnexpectedValueException('The stylesheet leaves a block unclosed.');
        }

        return $rules;
    }

    /**
     * @param class-string<HasColor> $policy
     *
     * @return array{package: string, path: string} the package directory and the path under `Colors/Components/`
     */
    private static function location(string $policy): array
    {
        if (preg_match('/^Sylament\\\\(?<package>[A-Za-z]+)\\\\Colors\\\\Components\\\\(?<path>.+)$/', $policy, $location) !== 1) {
            throw new LogicException(sprintf('The colour policy "%s" is not filed under its package\'s Colors/Components/.', $policy));
        }

        return ['package' => (string) u($location['package'])->kebab(), 'path' => $location['path']];
    }
}

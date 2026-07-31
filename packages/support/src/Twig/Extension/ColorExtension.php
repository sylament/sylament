<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Sylament\Support\Colors\ColorManager;
use Sylament\Support\Colors\Contracts\HasColor;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

use function Sylament\Support\blank;

class ColorExtension extends AbstractExtension
{
    public function __construct(
        private readonly ColorManager $colors,
    ) {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('colorAttributes', $this->colorAttributes(...)),
            new TwigFunction('sylamentStyles', $this->sylamentStyles(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }

    /**
     * @param class-string<HasColor> $policy
     * @param string|array<int, string>|null $color
     * @return array{class: list<string>, style: list<string>}
     */
    public function colorAttributes(string $policy, string|array|null $color): array
    {
        if (blank($color)) {
            return ['class' => [], 'style' => []];
        }

        if (is_array($color)) {
            return [
                'class' => ['sy-color'],
                'style' => $this->colors->getComponentCustomStyles($policy, $color),
            ];
        }

        return [
            'class' => $this->colors->getComponentClasses($policy, $color),
            'style' => [],
        ];
    }

    /**
     * The shipped stylesheet already carries the six default palettes and a swap class for each,
     * so only what a consumer registered on top of them needs emitting: `:root` values for a
     * palette the defaults define differently or not at all, and a swap class for a name the
     * stylesheet has none for. Both empty means an empty string rather than a bare element.
     *
     * A palette name is free-form, so it is stripped here; a shade key is not, because
     * `ColorManager::getColors()` rejects anything off the shade grid before it reaches this.
     */
    public function sylamentStyles(): Markup
    {
        $rootVariables = [];
        $swapClasses = [];

        foreach ($this->colors->getColors() as $name => $palette) {
            if ((ColorManager::DEFAULT_COLORS[$name] ?? null) === $palette) {
                continue;
            }

            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

            foreach ($palette as $shade => $color) {
                $rootVariables[] = "    --sy-{$safeName}-{$shade}: " . $this->colors->sanitizeCssColor($color) . ';';
            }

            if (array_key_exists($name, ColorManager::DEFAULT_COLORS)) {
                continue;
            }

            $swapClasses[] = ".sy-color-{$safeName} {\n" . implode("\n", array_map(
                fn (int $shade): string => "    --sy-color-{$shade}: var(--sy-{$safeName}-{$shade});",
                array_keys($palette),
            )) . "\n}";
        }

        $blocks = [];

        if ($rootVariables !== []) {
            $blocks[] = ":root {\n" . implode("\n", $rootVariables) . "\n}";
        }

        $blocks = [...$blocks, ...$swapClasses];

        if ($blocks === []) {
            return new Markup('', 'UTF-8');
        }

        return new Markup("<style>\n" . implode("\n\n", $blocks) . "\n</style>", 'UTF-8');
    }
}

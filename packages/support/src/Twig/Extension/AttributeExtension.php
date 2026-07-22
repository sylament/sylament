<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use RuntimeException;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AttributeExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('attrs', $this->renderAttributes(...), [
                'is_safe' => ['html'],
            ]),
            new TwigFunction('classes', $this->getClassTokens(...)),
        ];
    }

    /**
     * html_attr() replacement rendering through ComponentAttributeBag, so
     * templates and the PHP side share one attribute implementation. Sources
     * merge left to right: later scalars win, class/style token lists append
     * in source order; `null`, `false`, and `''` sources are skipped (the
     * implicit else of a ternary), any other string source throws. Class
     * values take strings, conditional maps, and nested lists directly — no
     * classes() wrapper needed here.
     *
     * @param iterable<string, mixed>|string|false|null $sources
     */
    public function renderAttributes(iterable|string|false|null ...$sources): string
    {
        $merged = new ComponentAttributeBag();

        foreach ($sources as $source) {
            if ($source === null) {
                continue;
            }

            if ($source === false) {
                continue;
            }

            if ($source === '') {
                continue;
            }

            if (is_string($source)) {
                throw new RuntimeException('Only empty strings may be passed as string sources to attrs(), to support the implicit else of a ternary.');
            }

            $merged = new ComponentAttributeBag([...$source])->merge($merged->getAttributes());
        }

        return $merged->toHtml();
    }

    /**
     * html_classes() replacement: builds the class token list (strings split on
     * whitespace, conditional maps resolve to their truthy keys, nested lists
     * flatten) and joins it into a space-separated string. Returns a string, not a
     * token-list object, so the one sink that renders it — ux-twig-component's
     * `attributes.defaults()`, which string-casts the value — never leaves an object
     * as an attribute value (PHP 8.5 deprecates wrapping one in an ArrayIterator).
     * attrs() re-splits such a string on `class`, so it stays usable there too.
     */
    public function getClassTokens(mixed ...$classes): string
    {
        return implode(' ', ComponentAttributeBag::toTokenList($classes));
    }
}

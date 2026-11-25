<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class MarkupExtension extends AbstractExtension
{
    /**
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('plain_text', $this->plainText(...)),
        ];
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('markup', $this->markup(...), [
                'needs_environment' => true,
            ]),
        ];
    }

    /**
     * @return array<TwigTest>
     */
    public function getTests(): array
    {
        return [
            new TwigTest('filled', filled(...)),
            new TwigTest('markup', static fn (mixed $value): bool => $value instanceof Markup),
        ];
    }

    public function markup(Environment $twig, ?string $content): ?Markup
    {
        if (blank($content)) {
            return null;
        }

        return new Markup($content, $twig->getCharset());
    }

    /**
     * A `Markup` as the text a reader sees, as Filament reads an `Htmlable` with `html_entity_decode(strip_tags(…))`;
     * the result is a plain string, so the sink escapes it again.
     */
    public function plainText(string|Markup|null $value): ?string
    {
        if (! $value instanceof Markup) {
            return $value;
        }

        return html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, $value->getCharset());
    }
}

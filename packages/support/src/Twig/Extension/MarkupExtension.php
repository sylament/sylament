<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

use function Sylament\Support\blank;

class MarkupExtension extends AbstractExtension
{
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

    public function markup(Environment $twig, ?string $content): ?Markup
    {
        if (blank($content)) {
            return null;
        }

        return new Markup($content, $twig->getCharset());
    }
}

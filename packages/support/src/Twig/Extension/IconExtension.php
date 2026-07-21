<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use BackedEnum;
use Sylament\Support\Enum\IconSize;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

use function Sylament\Support\generate_icon_html;

class IconExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('generateIconHtml', $this->generateIconHtml(...)),
        ];
    }

    /**
     * @param string|array<string>|null $alias
     * @param array<string, mixed>|null $attributes
     */
    public function generateIconHtml(
        string|BackedEnum|Markup|null $icon = null,
        string|array|null $alias = null,
        ?array $attributes = null,
        IconSize|string|null $size = null,
    ): ?Markup {
        if (is_string($size)) {
            $size = IconSize::tryFrom($size);
        }

        return generate_icon_html(
            $icon,
            $alias,
            $attributes === null ? null : new ComponentAttributeBag($attributes),
            $size,
        );
    }
}

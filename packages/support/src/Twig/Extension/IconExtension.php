<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use BackedEnum;
use Sylament\Support\Contracts\ScalableIcon;
use Sylament\Support\Enums\IconSize;
use Sylament\Support\Icons\IconManager;
use Sylament\Support\Twig\ComponentAttributeBag;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class IconExtension extends AbstractExtension
{
    public function __construct(
        private readonly IconManager $icons,
    ) {
    }

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
        if (filled($alias)) {
            $icon = $this->icons->resolve($alias) ?: $icon;
        }

        if (blank($icon)) {
            return null;
        }

        if (is_string($size)) {
            $size = IconSize::tryFrom($size);
        }

        $size ??= IconSize::Medium;

        $attributeBag = new ComponentAttributeBag($attributes ?? [])->merge([
            'class' => ['sy', 'sy-icon', "sy-size-{$size->value}"],
        ]);

        if ($icon instanceof Markup) {
            return new Markup("<span {$attributeBag->toHtml()}>{$icon}</span>", 'UTF-8');
        }

        if (is_string($icon) && str_contains($icon, '/')) {
            $attributeValues = $attributeBag->getAttributes();

            // An image path carries no accessible name of its own, so a screen reader
            // announces the filename. Default it to decorative unless the caller named it.
            if (
                blank($attributeValues['alt'] ?? null)
                && blank($attributeValues['aria-label'] ?? null)
                && blank($attributeValues['aria-labelledby'] ?? null)
            ) {
                $attributeBag = $attributeBag->merge(['alt' => '']);
            }

            $src = htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return new Markup("<img src=\"{$src}\" {$attributeBag->toHtml()} />", 'UTF-8');
        }

        if ($icon instanceof ScalableIcon) {
            $icon = $icon->getIconForSize($size);
        } elseif ($icon instanceof BackedEnum) {
            $icon = (string) $icon->value;
        }

        return new Markup($this->icons->renderIcon($icon, $attributeBag->toScalarAttributes()), 'UTF-8');
    }
}

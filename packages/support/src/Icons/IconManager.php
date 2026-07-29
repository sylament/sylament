<?php

declare(strict_types=1);

namespace Sylament\Support\Icons;

use BackedEnum;
use LogicException;
use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Icons\UXIconsBundle;
use Twig\Markup;

class IconManager
{
    /**
     * @var array<string, string|BackedEnum|Markup>
     */
    protected array $icons = [];

    public function __construct(
        protected readonly ?IconRendererInterface $renderer = null,
    ) {
    }

    /**
     * @param array<string, string|BackedEnum|Markup> $icons
     */
    public function register(array $icons): void
    {
        $this->icons = [
            ...$this->icons,
            ...$icons,
        ];
    }

    /**
     * @param string|array<string> $alias
     */
    public function resolve(string|array $alias): string|BackedEnum|Markup|null
    {
        foreach ((array) $alias as $singleAlias) {
            if (array_key_exists($singleAlias, $this->icons)) {
                return $this->icons[$singleAlias];
            }
        }

        return null;
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        if (!$this->renderer instanceof IconRendererInterface) {
            throw new LogicException(sprintf('Icon rendering requires symfony/ux-icons; register "%s" in your kernel.', UXIconsBundle::class));
        }

        return $this->renderer->renderIcon($name, $attributes);
    }
}

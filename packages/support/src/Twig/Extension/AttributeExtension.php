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
            new TwigFunction('styles', $this->getStyleDeclarations(...)),
        ];
    }

    /**
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

    public function getClassTokens(mixed ...$classes): string
    {
        return implode(' ', ComponentAttributeBag::toTokenList($classes));
    }

    public function getStyleDeclarations(mixed ...$styles): string
    {
        return implode('; ', ComponentAttributeBag::toDeclarationList($styles));
    }
}

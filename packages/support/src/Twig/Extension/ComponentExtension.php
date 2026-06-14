<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Sylament\Support\Component\ViewComponent;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ComponentExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderComponent', $this->renderComponent(...), [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function renderComponent(Environment $twig, ?ViewComponent $component): ?string
    {
        return $component?->render($twig);
    }
}

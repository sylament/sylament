<?php

declare(strict_types=1);

namespace Sylament\Support\Twig\Extension;

use Sylament\Support\Component\ViewComponent;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
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
            ]),
        ];
    }

    public function renderComponent(Environment $twig, ?ViewComponent $component): ?Markup
    {
        $html = $component?->render($twig);
        if ($html === null) {
            return null;
        }

        return new Markup($html, $twig->getCharset());
    }
}

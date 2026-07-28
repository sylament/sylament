<?php

declare(strict_types=1);

namespace Sylament\Actions\Twig\Extension;

use RuntimeException;
use Sylament\Actions\Action;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class ActionExtension extends AbstractExtension
{
    public function __construct(
        private readonly ComponentExtension $componentExtension,
    ) {
    }

    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderAction', $this->renderAction(...), [
                'needs_environment' => true,
                'needs_context' => true,
            ]),
        ];
    }

    /**
     * @param array<mixed> $context
     * @param array<string, mixed> $arguments
     */
    public function renderAction(Environment $twig, array $context, string $name, array $arguments = []): ?Markup
    {
        $component = $context['this'] ?? null;
        if (!$component instanceof InteractsWithActions) {
            throw new RuntimeException('Component must be an instance of ' . InteractsWithActions::class);
        }

        $action = $component->getAction($name);

        if (!$action instanceof Action) {
            return null;
        }

        if ($arguments !== []) {
            $action = $action($arguments);
        }

        if ($action->isHidden()) {
            return null;
        }

        return $this->componentExtension->renderComponent($twig, $action);
    }
}

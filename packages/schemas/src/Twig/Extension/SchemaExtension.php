<?php

declare(strict_types=1);

namespace Sylament\Schemas\Twig\Extension;

use RuntimeException;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Twig\Extension\ComponentExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class SchemaExtension extends AbstractExtension
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
            new TwigFunction('renderSchema', $this->renderSchema(...), [
                'needs_environment' => true,
                'needs_context' => true,
            ]),
        ];
    }

    /**
     * @param array<mixed> $context
     */
    public function renderSchema(Environment $twig, array $context, string $name): ?Markup
    {
        $component = $context['this'] ?? null;
        if (!$component instanceof InteractsWithSchemas) {
            throw new RuntimeException('Component must be a instance of ' . InteractsWithSchemas::class);
        }

        return $this->componentExtension->renderComponent($twig, $component->getSchema($name));
    }
}

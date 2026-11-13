<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;

trait InteractsWithRendering
{
    /**
     * Renders the component as a page renders a schema's field: the only child of a
     * schema registered on its host and bound to the host's `data` bag.
     */
    protected function renderSchemaComponent(Component $component): Crawler
    {
        $host = new class implements InteractsWithSchemas {
            use HasSchemas;

            /**
             * @var array<string, mixed>
             */
            public array $data = [];
        };

        $schema = $host->makeSchema()->statePath('data')->components([$component]);
        $host->cacheSchema('schema', $schema);

        $twig = static::getContainer()->get('twig');

        if (!$twig instanceof Environment) {
            throw new LogicException("The kernel's [twig] service is not a Twig environment.");
        }

        return new Crawler((string) $schema->render($twig));
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use LogicException;
use ReflectionProperty;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Components\ViewComponent;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\HttpKernelBrowser;
use Twig\Environment;

trait InteractsWithRendering
{
    /**
     * Renders the component as a page renders a schema's field: the only child of a
     * schema registered on its host and bound to the host's `data` bag, which holds
     * `$data` as a fill or a client's request left it.
     *
     * @param array<string, mixed> $data
     */
    protected function renderSchemaComponent(Component $component, array $data = []): Crawler
    {
        $host = new class implements InteractsWithSchemas {
            use HasSchemas;

            /**
             * @var array<string, mixed>
             */
            public array $data = [];
        };
        $host->data = $data;

        $schema = $host->makeSchema()->statePath('data')->components([$component]);
        $host->cacheSchema('schema', $schema);

        return new Crawler((string) $schema->render($this->twig()));
    }

    /**
     * Renders the component from the instance itself, as `render()` does outside a
     * schema or a delivery hop.
     */
    protected function renderViewComponent(ViewComponent $component): Crawler
    {
        return new Crawler($component->render($this->twig()) ?? throw new LogicException(sprintf('[%s] has no template to render.', $component::class)));
    }

    /**
     * Requests the page as a browser opens it, a GET through its route, and answers its
     * markup. The kernel catches no exception, so one the request throws reaches the
     * test, never an error page; a redirect is not followed, and its `Location` is named
     * instead.
     *
     * A given client opens the page with the session and cookies its earlier requests
     * left, and catches exceptions afterwards as it did before.
     */
    protected function visit(string $uri, ?KernelBrowser $client = null): Crawler
    {
        $client ??= static::getContainer()->get('test.client');

        if (!$client instanceof KernelBrowser) {
            throw new LogicException('The kernel has no [test.client]: turn framework.test on.');
        }

        $catchesExceptions = new ReflectionProperty(HttpKernelBrowser::class, 'catchExceptions')->getValue($client);

        if (!is_bool($catchesExceptions)) {
            throw new LogicException('The client keeps no [catchExceptions] flag to restore.');
        }

        $client->catchExceptions(false);

        try {
            $crawler = $client->request('GET', $uri);
        } finally {
            $client->catchExceptions($catchesExceptions);
        }

        $response = $client->getResponse();

        if ($response->isSuccessful()) {
            return $crawler;
        }

        if ($response->isRedirection()) {
            throw new LogicException(sprintf('[GET %s] redirected to [%s].', $uri, $response->headers->get('Location')));
        }

        throw new LogicException(sprintf('[GET %s] answered [%d] without throwing.', $uri, $response->getStatusCode()));
    }

    private function twig(): Environment
    {
        $twig = static::getContainer()->get('twig');

        if (!$twig instanceof Environment) {
            throw new LogicException("The kernel's [twig] service is not a Twig environment.");
        }

        return $twig;
    }
}

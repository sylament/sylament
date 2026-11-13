<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Concerns;

use LogicException;
use Sylament\Actions\Testing\ExceptionRecorder;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;
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

    /**
     * Requests the page as a browser opens it, a GET through its route, and answers its
     * markup. Any response but a success rethrows the exception the request threw, which
     * the actions bundle's test-mode recorder kept, never an error page; a redirect is
     * not followed, and its `Location` is named instead.
     */
    protected function visit(string $uri): Crawler
    {
        $client = static::getContainer()->get('test.client');

        if (!$client instanceof KernelBrowser) {
            throw new LogicException('The kernel has no [test.client]: turn framework.test on.');
        }

        $crawler = $client->request('GET', $uri);
        $container = $client->getContainer();

        if (!$container->has(ExceptionRecorder::class)) {
            throw new LogicException(sprintf('The kernel has no [%s]: SylamentActionsBundle registers it when framework.test is on.', ExceptionRecorder::class));
        }

        $recorder = $container->get(ExceptionRecorder::class);

        if (!$recorder instanceof ExceptionRecorder) {
            throw new LogicException(sprintf('The kernel has no [%s]: SylamentActionsBundle registers it when framework.test is on.', ExceptionRecorder::class));
        }

        $throwable = $recorder->takeThrowable();
        $response = $client->getResponse();

        if ($response->isSuccessful()) {
            return $crawler;
        }

        if (!$throwable instanceof Throwable && $response->isRedirection()) {
            throw new LogicException(sprintf('[GET %s] redirected to [%s].', $uri, $response->headers->get('Location')));
        }

        throw $throwable ?? new LogicException(sprintf('[GET %s] answered [%d] without an exception to rethrow.', $uri, $response->getStatusCode()));
    }
}

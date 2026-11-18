<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

/**
 * Renders the tables live host its route names as a page, so the host mounts inside the
 * request and reads the session the client's earlier requests left.
 */
#[AsController]
final class TablePageController
{
    public static function url(string $component): string
    {
        return '/tables/page/' . $component;
    }

    public function __invoke(string $component, Environment $twig): Response
    {
        return new Response($twig->render('@Fixtures/tables/page.html.twig', ['name' => $component]));
    }
}

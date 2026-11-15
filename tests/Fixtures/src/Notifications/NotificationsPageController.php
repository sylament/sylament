<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Notifications;

use Sylament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Twig\Environment;

/**
 * Sends a toast and renders a page that places a live component before the stack and
 * renders the stack twice, as a controller sends before its layout renders.
 */
#[AsController]
final class NotificationsPageController
{
    public const string URL = '/notifications/page';

    public function __invoke(Environment $twig): Response
    {
        Notification::make()->title('Welcome')->send();

        return new Response($twig->render('@Fixtures/notifications/page.html.twig'));
    }
}

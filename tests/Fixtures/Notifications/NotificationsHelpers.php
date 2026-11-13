<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Notifications;

use LogicException;
use Sylament\Actions\Testing\Testable;
use Sylament\Notifications\Notification;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

trait NotificationsHelpers
{
    /**
     * Renders the toast from the instance itself, as `render()` does outside the delivery hop.
     */
    protected function renderNotification(Notification $notification): Crawler
    {
        $twig = static::getContainer()->get('twig');

        if (!$twig instanceof Environment) {
            throw new LogicException("The kernel's [twig] service is not a Twig environment.");
        }

        return new Crawler($notification->render($twig) ?? throw new LogicException('The notification has no template to render.'));
    }

    /**
     * Starts a request that carries the session, as a page's request does, so a test can
     * send a toast and render the stack in-process.
     */
    protected function startSessionRequest(SessionInterface $session = new Session(new MockArraySessionStorage())): SessionInterface
    {
        $requestStack = static::getContainer()->get('request_stack');

        if (!$requestStack instanceof RequestStack) {
            throw new LogicException("The kernel's [request_stack] service is not a request stack.");
        }

        $request = new Request();
        $request->setSession($session);

        $requestStack->push($request);

        return $session;
    }

    /**
     * The toasts the tester's last response hands the stack, read from the
     * `sylament:notification` browser events UX writes onto the host's root.
     */
    protected function deliveredToasts(Testable $tester): Crawler
    {
        $root = $tester->render()->crawler()->filter('[data-live-events-to-dispatch-value]');

        if ($root->count() === 0) {
            return new Crawler();
        }

        $events = json_decode((string) $root->attr('data-live-events-to-dispatch-value'), true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($events)) {
            throw new LogicException('The browser events to dispatch are not a list.');
        }

        $html = '';

        foreach ($events as $event) {
            if (!is_array($event) || ($event['event'] ?? null) !== 'sylament:notification') {
                continue;
            }

            $payload = $event['payload'] ?? null;

            if (!is_array($payload) || !is_string($payload['html'] ?? null)) {
                throw new LogicException('A [sylament:notification] event carries no toast markup.');
            }

            $html .= $payload['html'];
        }

        return new Crawler($html);
    }
}

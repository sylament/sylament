<?php

declare(strict_types=1);

namespace Sylament\Notifications\Delivery;

use LogicException;
use Sylament\Notifications\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;

class LiveDeliverySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SessionNotifications $sessionNotifications,
        private readonly LiveResponder $liveResponder,
        private readonly Environment $twig,
    ) {
    }

    /**
     * @return array<class-string, array{string, int}>
     */
    public static function getSubscribedEvents(): array
    {
        // Priority 20: LiveResponder's queues are drained into the component's root
        // attributes by AddLiveAttributesSubscriber on this same event at priority 0,
        // so the notifications must be queued before it runs.
        return [PreRenderEvent::class => ['onPreRender', 20]];
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        if ($event->getMetadata()->get('live', false) !== true) {
            return;
        }

        $request = $this->requestStack->getMainRequest();

        if (!$request instanceof Request || ! $request->attributes->has('_live_component')) {
            return;
        }

        foreach ($this->sessionNotifications->pull() as $notification) {
            $html = Notification::fromArray($notification)->render($this->twig)
                ?? throw new LogicException('The notification template rendered to null.');

            $this->liveResponder->dispatchBrowserEvent('sylament:notification', ['html' => $html]);
        }
    }
}

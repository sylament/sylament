<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Notifications;

use Sylament\Notifications\Notification;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function Symfony\Component\Translation\t;

/**
 * Sends toasts from its live actions, as a page's host does, or while it renders when
 * mounted with `notifiesWhileRendering`; its template renders a plain Twig component
 * after that render-time send.
 */
#[AsLiveComponent(name: 'Notifications:NotifyingHost', template: '@Fixtures/notifications/NotifyingHost.html.twig')]
final class NotifyingHost
{
    use DefaultActionTrait;

    #[LiveProp]
    public bool $notifiesWhileRendering = false;

    #[LiveAction]
    public function save(): void
    {
        Notification::make()->success()->title('Saved')->send();
        Notification::make()->title('Synced')->send();
    }

    #[LiveAction]
    public function saveAndLeave(): RedirectResponse
    {
        Notification::make()->success()->title('Saved')->send();

        return new RedirectResponse(NotificationsPageController::URL);
    }

    #[LiveAction]
    public function saveTranslatedAndLeave(): RedirectResponse
    {
        Notification::make()->success()->title(t('saved', [], 'fixtures-notifications'))->send();

        return new RedirectResponse(NotificationsPageController::URL);
    }

    public function getRenderNotice(): string
    {
        Notification::make()->title('Rendered')->send();

        return '';
    }
}

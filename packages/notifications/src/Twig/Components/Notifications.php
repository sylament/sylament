<?php

declare(strict_types=1);

namespace Sylament\Notifications\Twig\Components;

use Sylament\Notifications\Delivery\SessionNotifications;
use Sylament\Notifications\Notification;
use Sylament\Support\Enums\Alignment;
use Sylament\Support\Enums\VerticalAlignment;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    name: 'SylamentNotifications:Notifications',
    template: '@SylamentNotifications/components/Notifications/index.html.twig',
)]
class Notifications
{
    public string $alignment = 'right';

    public string $verticalAlignment = 'start';

    public function __construct(
        private readonly SessionNotifications $sessionNotifications,
    ) {
    }

    public function mount(Alignment|string|null $alignment = null, VerticalAlignment|string|null $verticalAlignment = null): void
    {
        if ($alignment !== null) {
            $this->alignment = $alignment instanceof Alignment ? $alignment->value : $alignment;
        }

        if ($verticalAlignment !== null) {
            $this->verticalAlignment = $verticalAlignment instanceof VerticalAlignment ? $verticalAlignment->value : $verticalAlignment;
        }
    }

    /**
     * @return list<Notification>
     */
    public function getFlashed(): array
    {
        return array_map(Notification::fromArray(...), $this->sessionNotifications->pull());
    }
}

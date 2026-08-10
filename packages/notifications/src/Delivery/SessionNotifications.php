<?php

declare(strict_types=1);

namespace Sylament\Notifications\Delivery;

use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class SessionNotifications
{
    public const string FLASH_KEY = 'sylament.notifications';

    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<string, mixed> $notification
     */
    public function push(array $notification): void
    {
        $this->getFlashBag()->add(self::FLASH_KEY, $notification);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pull(): array
    {
        $notifications = [];

        foreach ($this->getFlashBag()->get(self::FLASH_KEY) as $notification) {
            $entry = $this->toEntry($notification);

            if ($entry !== null) {
                $notifications[] = $entry;
            }
        }

        return $notifications;
    }

    /**
     * @return array<string, mixed>|null null when the flash entry is not a string-keyed array
     */
    private function toEntry(mixed $notification): ?array
    {
        if (! is_array($notification)) {
            return null;
        }

        $entry = [];

        foreach ($notification as $key => $value) {
            if (! is_string($key)) {
                return null;
            }

            $entry[$key] = $value;
        }

        return $entry;
    }

    private function getFlashBag(): FlashBagInterface
    {
        $session = $this->requestStack->getSession();

        if (! $session instanceof FlashBagAwareSessionInterface) {
            throw new LogicException(sprintf('Notifications need a flash-bag-aware session, got "%s".', $session::class));
        }

        return $session->getFlashBag();
    }
}

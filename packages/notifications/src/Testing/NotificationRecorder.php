<?php

declare(strict_types=1);

namespace Sylament\Notifications\Testing;

use Sylament\Notifications\Delivery\SessionNotifications;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps every notification pushed since the kernel last reset its services, so a tester
 * can read what a request sent after the stack has drained it into the response.
 */
final class NotificationRecorder extends SessionNotifications implements ResetInterface
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $sent = [];

    public function __construct(
        private readonly SessionNotifications $inner,
    ) {
    }

    /**
     * @param array<string, mixed> $notification
     */
    public function push(array $notification): void
    {
        $this->sent[] = $notification;
        $this->inner->push($notification);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pull(): array
    {
        return $this->inner->pull();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sent(): array
    {
        return $this->sent;
    }

    public function reset(): void
    {
        $this->sent = [];
    }
}

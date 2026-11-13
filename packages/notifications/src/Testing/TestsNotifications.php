<?php

declare(strict_types=1);

namespace Sylament\Notifications\Testing;

use PHPUnit\Framework\Assert;
use Sylament\Notifications\Notification;

use function Sylament\Support\blank;
use function Sylament\Support\except;

/**
 * Filament's `Notification::assertNotified()` and `assertNotNotified()`, matching against
 * the notifications a tester collected from its requests.
 */
final class TestsNotifications
{
    /**
     * @param list<array<string, mixed>> $sent
     */
    public static function assertNotified(array $sent, Notification|string|null $notification = null): void
    {
        $expectedNotification = self::findSentNotification($sent, $notification);

        if (blank($notification)) {
            Assert::assertNotEmpty(
                $sent,
                'A notification was expected but none were sent.',
            );

            return;
        }

        Assert::assertNotNull($expectedNotification, 'A notification was not sent');

        if ($notification instanceof Notification) {
            Assert::assertSame(
                except($expectedNotification, ['id']),
                except($notification->toArray(), ['id']),
            );

            return;
        }

        Assert::assertSame($expectedNotification['title'] ?? null, $notification);
    }

    /**
     * @param list<array<string, mixed>> $sent
     */
    public static function assertNotNotified(array $sent, Notification|string|null $notification = null): void
    {
        $expectedNotification = self::findSentNotification($sent, $notification);

        if (blank($notification)) {
            Assert::assertEmpty(
                $sent,
                'No notification was expected but at least one was sent.',
            );

            return;
        }

        if ($notification instanceof Notification) {
            Assert::assertNotSame(
                except($expectedNotification ?? [], ['id']),
                except($notification->toArray(), ['id']),
                'The notification with the given configuration was sent',
            );

            return;
        }

        if ($expectedNotification !== null) {
            Assert::assertNotSame(
                $expectedNotification['title'] ?? null,
                $notification,
                'The notification with the given title was sent',
            );
        }
    }

    /**
     * A title matches by its string, a `Notification` by its whole array but the id.
     *
     * @param list<array<string, mixed>> $sent
     * @return array<string, mixed>|null
     */
    private static function findSentNotification(array $sent, Notification|string|null $notification): ?array
    {
        if (is_string($notification)) {
            return array_find($sent, fn (array $sentNotification): bool => ($sentNotification['title'] ?? null) === $notification);
        }

        if ($notification instanceof Notification) {
            $expectedNotification = except($notification->toArray(), ['id']);

            return array_find($sent, fn (array $sentNotification): bool => except($sentNotification, ['id']) === $expectedNotification);
        }

        return null;
    }
}

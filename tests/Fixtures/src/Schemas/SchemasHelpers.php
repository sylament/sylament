<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use LogicException;
use Sylament\Actions\Testing\Testable;

trait SchemasHelpers
{
    /**
     * The payload of each `$event` browser event the tester's last response dispatches, in
     * order, read from the events UX writes onto the host's root.
     *
     * @return list<array<array-key, mixed>>
     */
    protected function dispatchedBrowserEvents(Testable $tester, string $event): array
    {
        $root = $tester->render()->crawler()->filter('[data-live-events-to-dispatch-value]');

        if ($root->count() === 0) {
            return [];
        }

        $events = json_decode((string) $root->attr('data-live-events-to-dispatch-value'), true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($events)) {
            throw new LogicException('The browser events to dispatch are not a list.');
        }

        $payloads = [];

        foreach ($events as $dispatched) {
            if (!is_array($dispatched) || ($dispatched['event'] ?? null) !== $event) {
                continue;
            }

            $payload = $dispatched['payload'] ?? null;

            if (!is_array($payload)) {
                throw new LogicException(sprintf('A [%s] event carries no payload.', $event));
            }

            $payloads[] = $payload;
        }

        return $payloads;
    }
}

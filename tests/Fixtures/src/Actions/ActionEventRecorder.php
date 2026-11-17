<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Events\ActionCalled;
use Sylament\Actions\Events\ActionCalling;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Records in the request's {@see HookLog} the lifecycle events of the actions a
 * {@see HookedModalHost} hosts, and of no other host, whose tests read the log exactly.
 */
final readonly class ActionEventRecorder implements EventSubscriberInterface
{
    public function __construct(
        private HookLog $hookLog,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ActionCalling::class => 'onActionCalling',
            ActionCalled::class => 'onActionCalled',
        ];
    }

    public function onActionCalling(ActionCalling $event): void
    {
        if ($event->getAction()->getLiveComponentOrNull() instanceof HookedModalHost) {
            $this->hookLog->record("calling {$event->getAction()->getName()}");
        }
    }

    public function onActionCalled(ActionCalled $event): void
    {
        if ($event->getAction()->getLiveComponentOrNull() instanceof HookedModalHost) {
            $this->hookLog->record("called {$event->getAction()->getName()}");
        }
    }
}

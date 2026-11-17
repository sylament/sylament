<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Listens for the `restocked` event and records the SKU it carried in the request's
 * {@see HookLog}.
 */
#[AsLiveComponent(name: 'Actions:ListeningHost', template: '@Fixtures/actions/ListeningHost.html.twig')]
class ListeningHost
{
    use DefaultActionTrait;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    #[LiveListener('restocked')]
    public function onRestocked(#[LiveArg] string $sku): void
    {
        $this->hookLog->record("restocked {$sku}");
    }
}

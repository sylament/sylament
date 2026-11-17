<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Answers its `pause` action with a bare 503, a failure no exception explains.
 */
#[AsLiveComponent(name: 'Actions:UnavailableHost', template: '@Fixtures/actions/UnavailableHost.html.twig')]
class UnavailableHost
{
    use DefaultActionTrait;

    #[LiveAction]
    public function pause(): Response
    {
        return new Response(status: Response::HTTP_SERVICE_UNAVAILABLE);
    }
}

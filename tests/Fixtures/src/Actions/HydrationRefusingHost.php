<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Refuses with a 422 while it hydrates with `refusing` set, before UX has mounted it,
 * so the answer is the framework's error page rather than UX's re-render.
 */
#[AsLiveComponent(name: 'Actions:HydrationRefusingHost', template: '@Fixtures/actions/HydrationRefusingHost.html.twig')]
class HydrationRefusingHost
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public bool $refusing = false;

    #[PostHydrate]
    public function refuseWhileHydrating(): void
    {
        if ($this->refusing) {
            throw new UnprocessableEntityHttpException('Refused while hydrating.');
        }
    }
}

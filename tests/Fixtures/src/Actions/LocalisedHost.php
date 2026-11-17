<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Answers on the localised live route, so its render shows the locale the request
 * carried, and redirects to `/` from its `leave` action.
 */
#[AsLiveComponent(name: 'Actions:LocalisedHost', template: '@Fixtures/actions/LocalisedHost.html.twig', route: 'sylament_tests_actions_localised_live')]
class LocalisedHost
{
    use DefaultActionTrait;

    #[LiveAction]
    public function leave(): RedirectResponse
    {
        return new RedirectResponse('/');
    }
}

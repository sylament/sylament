<?php

declare(strict_types=1);

namespace Sylament\Notifications\Tests\Fixtures\Twig;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'NotificationsFixtures:Dummy', template: '@NotificationsFixtures/Dummy.html.twig')]
final class DummyLiveComponent
{
    use DefaultActionTrait;
}

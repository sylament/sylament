<?php

declare(strict_types=1);

namespace Sylament\Notifications\Tests\Fixtures\Twig;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'NotificationsFixtures:Plain', template: '@NotificationsFixtures/Plain.html.twig')]
final class PlainComponent
{
}

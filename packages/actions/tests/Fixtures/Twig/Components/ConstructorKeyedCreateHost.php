<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\ConstructorKeyedSeat;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

#[AsLiveComponent(name: 'ConstructorKeyedCreateHost', template: '@ActionsFixtures/components/RelationshipCreateHost.html.twig')]
final class ConstructorKeyedCreateHost extends RelationshipCreateHost
{
    /**
     * @return class-string
     */
    public function getDefaultActionRecordClass(Action $action): string
    {
        return ConstructorKeyedSeat::class;
    }
}

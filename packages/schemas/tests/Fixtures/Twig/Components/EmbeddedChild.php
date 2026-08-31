<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The child a {@see \Sylament\Schemas\Components\LiveComponent} embed mounts:
 * a live component of its own, carrying one prop so the embed's `data()` can
 * be read back out of the rendered HTML.
 */
#[AsLiveComponent(name: 'EmbeddedChild', template: '@SchemasFixtures/components/EmbeddedChild.html.twig')]
class EmbeddedChild
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $label = 'child';
}

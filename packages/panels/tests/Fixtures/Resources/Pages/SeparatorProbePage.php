<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Records the raw key a separator path hands a page, resolving no record at
 * all, so the route round trip is assertable without a Doctrine fixture. The
 * same class is mounted at the resource index too, whose route carries no
 * `record` parameter, so the mount parameter has to default.
 */
#[AsLiveComponent]
final class SeparatorProbePage extends Page
{
    #[LiveProp]
    public ?string $record = null;

    protected ?string $template = '@PanelsFixtures/separator_probe.html.twig';

    public function mount(?string $record = null): void
    {
        $this->record = $record;
    }
}

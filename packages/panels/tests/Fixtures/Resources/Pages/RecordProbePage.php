<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent]
final class RecordProbePage extends Page
{
    /**
     * The same class is mounted at the resource index too, whose route carries no
     * `record` parameter, so the mount parameter has to default.
     */
    #[LiveProp]
    public ?string $record = null;

    protected ?string $template = '@PanelsFixtures/record_probe.html.twig';

    public function mount(?string $record = null): void
    {
        if ($record === 'missing') {
            throw new NotFoundHttpException('probe record not found');
        }

        $this->record = $record;
    }
}

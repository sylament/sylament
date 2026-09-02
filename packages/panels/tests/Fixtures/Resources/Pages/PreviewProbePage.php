<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Tests\Fixtures\Resources\ProbeResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A user-authored resource page: a record of its own, a body block over the base
 * partial and a header link its own resource mints.
 */
#[AsLiveComponent]
final class PreviewProbePage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ProbeResource::class;

    protected static ?string $breadcrumb = 'Preview';

    protected static ?string $navigationLabel = 'Preview';

    protected ?string $template = '@PanelsFixtures/preview_probe.html.twig';

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);
    }

    /**
     * @return array<Action|ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return [Action::make('back')->label('Back')->url($this->getResourceUrl('index'))];
    }
}

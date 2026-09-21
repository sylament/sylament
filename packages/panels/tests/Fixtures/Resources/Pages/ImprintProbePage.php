<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord;
use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Tests\Fixtures\Resources\ImprintResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A custom record page over a composite record: it renders the key it was
 * mounted with and the sibling page URL it mints from the record it holds.
 */
#[AsLiveComponent]
final class ImprintProbePage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ImprintResource::class;

    protected static ?string $breadcrumb = 'Probe';

    protected static ?string $navigationLabel = 'Probe';

    protected ?string $template = '@PanelsFixtures/imprint_probe.html.twig';

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use Sylament\Panels\Pages\Page;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

/**
 * Records the mount arguments a nested route hands a page, so the parameter
 * bridge is assertable before the record pages themselves take
 * `parentRecords` — a prop left null is one the controller never passed, which
 * is what tells a flat route apart from a nested one carrying no ancestors.
 */
#[AsLiveComponent]
final class ParentRecordProbePage extends Page
{
    /**
     * @var array<string, string>|null
     */
    #[LiveProp]
    public ?array $parentRecords = null;

    #[LiveProp]
    public ?string $record = null;

    protected ?string $template = '@PanelsFixtures/parent_record_probe.html.twig';

    /**
     * @param array<string, string>|null $parentRecords
     */
    public function mount(?string $record = null, ?array $parentRecords = null): void
    {
        $this->record = $record;
        $this->parentRecords = $parentRecords;
    }
}

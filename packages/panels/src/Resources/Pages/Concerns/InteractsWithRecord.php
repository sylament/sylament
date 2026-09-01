<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages\Concerns;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

/**
 * The one record a page is opened on: the route key the wire carries, the
 * record it resolves to, and the title both are read through.
 */
trait InteractsWithRecord
{
    /**
     * The record's route key, never the record itself — the wire carries a
     * scalar and `getRecord()` resolves it. The type is a single nullable
     * string rather than Filament's `int|string`, because
     * `LiveComponentMetadataFactory` throws on any union-typed `LiveProp`;
     * `mount()` takes the union instead.
     */
    #[LiveProp(dehydrateWith: 'dehydrateRecord')]
    public ?string $record = null;

    protected ?object $resolvedRecord = null;

    /**
     * The parent keys are assigned before the record key, and nothing else is
     * assigned here: a page resolves its record inside its own mount tail, and
     * a chain arriving after that would scope nothing.
     *
     * @param array<string, string> $parentRecords
     */
    public function mountRecord(int|string $record, array $parentRecords = []): void
    {
        $this->parentRecords = $parentRecords;
        $this->record = (string) $record;
    }

    /**
     * The wire value is the route key the property holds, not the entity
     * `PropertyAccessor` reads out of the same-named `getRecord()`.
     */
    public function dehydrateRecord(mixed $value): ?string
    {
        return $this->record;
    }

    public function hasRecord(): bool
    {
        return filled($this->record);
    }

    /**
     * The record's route key as the wire carries it — the value an embedded
     * child is mounted with, where the record object itself cannot travel.
     */
    public function getRecordKey(): ?string
    {
        return $this->record;
    }

    public function getRecord(): object
    {
        if ($this->resolvedRecord !== null) {
            return $this->resolvedRecord;
        }

        $key = $this->record;

        if (blank($key)) {
            throw new NotFoundHttpException(sprintf('The page [%s] carries no record key to resolve.', static::class));
        }

        $record = $this->getResourceInstance()->resolveRecordRouteBinding($key);

        if ($record === null) {
            throw new NotFoundHttpException(sprintf('No record matching [%s] exists for the resource [%s].', $key, static::getResource()));
        }

        return $this->resolvedRecord = $record;
    }

    /**
     * Pre-mount safe: `PageController` reads the document title off an
     * un-mounted page, where the record key is still null.
     */
    public function getRecordTitle(): string
    {
        $resource = $this->getResourceInstance();

        if ($this->record === null) {
            return $resource->getTitleCaseRecordLabel();
        }

        return $resource->hasRecordTitle()
            ? $resource->getRecordTitle($this->getRecord())
            : $resource->getTitleCaseRecordLabel();
    }
}

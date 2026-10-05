<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use LogicException;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Resources\CountermarkResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A nested create whose makeRecord() answers the parent record itself.
 */
#[AsLiveComponent]
final class CreateCountermarkAsMill extends CreateRecord
{
    protected static string $resource = CountermarkResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function makeRecord(array $data): object
    {
        return $this->getParentRecord() ?? throw new LogicException('This page is mounted under a mill.');
    }
}

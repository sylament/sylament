<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources\Pages;

use LogicException;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Countermark;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Mill;
use Sylament\Panels\Tests\Fixtures\Resources\CountermarkResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Builds the countermark under the page's own parent mill, counting its calls.
 */
#[AsLiveComponent]
final class CreateCountermark extends CreateRecord
{
    public static int $madeRecords = 0;

    protected static string $resource = CountermarkResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function makeRecord(array $data): Countermark
    {
        self::$madeRecords++;

        $mill = $this->getParentRecord();
        $initials = $data['initials'] ?? null;

        if (! $mill instanceof Mill || ! is_string($initials)) {
            throw new LogicException('A countermark is made under a mill, from the initials the form maps as a string.');
        }

        return new Countermark($mill, $initials);
    }
}

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
 * A nested create whose makeRecord() builds the countermark under the mill
 * named Other rather than the page's own parent.
 */
#[AsLiveComponent]
final class CreateCountermarkUnderAnotherMill extends CreateRecord
{
    protected static string $resource = CountermarkResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function makeRecord(array $data): Countermark
    {
        $other = $this->getResourceInstance()->getRecordQuery()->getEntityManager()->getRepository(Mill::class)->findOneBy(['name' => 'Other']);
        $initials = $data['initials'] ?? null;

        if (! $other instanceof Mill || ! is_string($initials)) {
            throw new LogicException('The other mill is seeded under the name Other, and the form maps the initials as a string.');
        }

        return new Countermark($other, $initials);
    }
}

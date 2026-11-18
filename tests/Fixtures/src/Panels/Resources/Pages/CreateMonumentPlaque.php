<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use LogicException;
use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Entity\Monument;
use Sylament\Tests\Fixtures\Entity\MonumentPlaque;
use Sylament\Tests\Fixtures\Panels\Resources\MonumentPlaqueResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Builds the plaque with the page's own parent monument, or, for {@see self::STRANGER_LOCALE}, with a monument of its own.
 */
#[AsLiveComponent]
final class CreateMonumentPlaque extends CreateRecord
{
    public const string STRANGER_LOCALE = 'xx';

    protected static string $resource = MonumentPlaqueResource::class;

    protected function makeRecord(array $data): MonumentPlaque
    {
        $locale = is_string($data['locale'] ?? null) ? $data['locale'] : '';
        $monument = $locale === self::STRANGER_LOCALE ? new Monument('Elsewhere') : $this->getParentRecord();

        if (!$monument instanceof Monument) {
            throw new LogicException('The plaque page holds no parent monument.');
        }

        return new MonumentPlaque($monument, $locale);
    }
}

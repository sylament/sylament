<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A value object embedded in `Address`, itself embedded in `Tagged`: the nested
 * level Doctrine maps under the dotted key `address.geo`, which no root property
 * accessor reaches.
 */
#[ORM\Embeddable]
class Geo
{
    public function __construct(
        #[ORM\Column(length: 255)]
        private string $city = ''
    ) {
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }
}

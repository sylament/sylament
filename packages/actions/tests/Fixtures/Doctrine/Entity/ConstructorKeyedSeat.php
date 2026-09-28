<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final readonly class ConstructorKeyedSeat
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 255)]
        public string $code,
    ) {
    }
}

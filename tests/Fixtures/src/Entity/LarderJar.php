<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see Larder::$jars} with a generated key, constructible without arguments:
 * a label, a date, an enum, a supplier of its own and the position its larder orders it by.
 */
#[ORM\Entity]
class LarderJar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'jars')]
    public ?Larder $larder = null;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $label = '',
        #[ORM\Column(nullable: true)]
        public ?DateTimeImmutable $sealedOn = null,
        #[ORM\Column(nullable: true, enumType: JarSeal::class)]
        public ?JarSeal $seal = null,
        #[ORM\ManyToOne]
        public ?Product $supplier = null,
        #[ORM\Column]
        public int $position = 0,
    ) {
    }
}

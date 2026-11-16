<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * The owning side of {@see Lighthouse}'s one-to-one, constructible without arguments,
 * with a string, a date, an enum and an association among its leaves.
 */
#[ORM\Entity]
class LighthouseLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\OneToOne(inversedBy: 'log')]
    public ?Lighthouse $lighthouse = null;

    #[ORM\Column]
    public string $keeper = '';

    #[ORM\Column(nullable: true)]
    public ?DateTimeImmutable $openedOn = null;

    #[ORM\Column(nullable: true, enumType: LighthouseBeam::class)]
    public ?LighthouseBeam $beam = null;

    #[ORM\ManyToOne]
    public ?Product $supplier = null;
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A record declaring constraints of its own, a capped title and a unique slug, under a
 * single key the application assigns. Each property is nullable, so no field over it
 * derives a requirement and the record's own constraints answer alone.
 */
#[ORM\Entity]
#[UniqueEntity('slug')]
class CappedHeadline
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 16)]
        public ?string $code = null,
        #[ORM\Column(length: 32)]
        #[Assert\Length(max: 5)]
        public ?string $title = null,
        #[ORM\Column(length: 32, unique: true)]
        public ?string $slug = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Declares {@see RevisionedNote}'s composite identifier on a mapped superclass.
 */
#[ORM\MappedSuperclass]
abstract class RevisionedBase
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 32)]
        public string $code,
        #[ORM\Id]
        #[ORM\Column]
        public int $revision,
    ) {
    }
}

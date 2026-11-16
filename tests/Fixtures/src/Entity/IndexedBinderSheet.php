<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A sheet of {@see IndexedBinder::$sheets}, which the collection holds under its code, with
 * a capped heading.
 */
#[ORM\Entity]
class IndexedBinderSheet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'sheets')]
    public ?IndexedBinder $binder = null;

    public function __construct(
        #[ORM\Column(length: 16)]
        public string $code = '',
        #[ORM\Column(length: 32)]
        #[Assert\Length(max: 5, maxMessage: 'A sheet heading is too long.')]
        public string $heading = '',
    ) {
    }
}

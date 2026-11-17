<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see LarderBin::$notes} with a generated key.
 */
#[ORM\Entity]
class LarderBinNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\ManyToOne(inversedBy: 'notes')]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public ?LarderBin $bin = null;

    public function __construct(
        #[ORM\Column(length: 40)]
        public string $text = '',
    ) {
    }
}

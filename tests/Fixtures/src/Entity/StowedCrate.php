<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A record carrying every column kind a copy of it treats apart: a generated id, a
 * version, a column the database generates on insert, a column no insert writes, a plain
 * column, and an embeddable with another nested inside it.
 */
#[ORM\Entity]
class StowedCrate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Version]
    #[ORM\Column(type: 'integer')]
    public int $revision = 1;

    #[ORM\Column(insertable: false, updatable: false, options: ['default' => 7], generated: 'INSERT')]
    public int $sequence = 0;

    #[ORM\Column(length: 16, insertable: false, options: ['default' => 'pending'])]
    public string $status = 'pending';

    #[ORM\Embedded]
    public StowageLabel $label;

    public function __construct(
        #[ORM\Column(length: 32)]
        public string $contents = '',
        string $port = '',
        string $deck = '',
    ) {
        $this->label = new StowageLabel($port, $deck);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An owner of three to-one records whose keys the application assigns: one keyed by two
 * strings, one keyed by this berth and a string, which the setter fills as it writes the
 * relationship, and one keyed by a single string. Each key half is nullable, so no field
 * over it derives a requirement, and every one cascades a persist.
 */
#[ORM\Entity]
class Berth
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column]
    public string $name = '';

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'mooring_pier', referencedColumnName: 'pier')]
    #[ORM\JoinColumn(name: 'mooring_slot', referencedColumnName: 'slot')]
    public ?BerthMooring $mooring = null;

    #[ORM\OneToOne(cascade: ['persist'])]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public ?BerthBuoy $buoy = null;

    #[ORM\OneToOne(mappedBy: 'berth', cascade: ['persist'])]
    private ?BerthCleat $cleat = null;

    public function getCleat(): ?BerthCleat
    {
        return $this->cleat;
    }

    public function setCleat(?BerthCleat $cleat): void
    {
        if ($cleat instanceof BerthCleat) {
            $cleat->berth = $this;
        }

        $this->cleat = $cleat;
    }
}

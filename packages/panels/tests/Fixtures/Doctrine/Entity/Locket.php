<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity whose no-argument constructor builds the record it
 * derives from, so a create that never maps the clasp's code persists a locket
 * whose whole key flattens to `''` — the identity hash Doctrine can neither
 * detach nor remove.
 */
#[ORM\Entity]
class Locket
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: Clasp::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'clasp_code', referencedColumnName: 'code')]
    private Clasp $clasp;

    #[ORM\Column(length: 255)]
    private string $engraving = '';

    public function __construct()
    {
        $this->clasp = new Clasp();
    }

    public function getClasp(): Clasp
    {
        return $this->clasp;
    }

    public function getEngraving(): string
    {
        return $this->engraving;
    }

    public function setEngraving(string $engraving): void
    {
        $this->engraving = $engraving;
    }
}

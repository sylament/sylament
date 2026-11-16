<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An owner of every to-one shape a layout component entangles with: the inverse side of
 * a one-to-one whose setter keeps the owning side in step and whose cascade persists a
 * record built for it, without `orphanRemoval`; a to-one onto a target that needs
 * constructor arguments; one whose property does not accept null; and a collection.
 */
#[ORM\Entity]
class Lighthouse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public int $id;

    #[ORM\Column]
    public string $name = '';

    #[ORM\ManyToOne]
    public ?Invoice $invoice = null;

    #[ORM\ManyToOne]
    public Product $lamp;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class)]
    #[ORM\JoinTable(name: 'lighthouse_spare')]
    public Collection $spares;

    #[ORM\OneToOne(mappedBy: 'lighthouse', cascade: ['persist'])]
    private ?LighthouseLog $log = null;

    public function __construct()
    {
        $this->spares = new ArrayCollection();
    }

    public function getLog(): ?LighthouseLog
    {
        return $this->log;
    }

    public function setLog(?LighthouseLog $log): void
    {
        if ($log instanceof LighthouseLog) {
            $log->lighthouse = $this;
        }

        $this->log = $log;
    }
}

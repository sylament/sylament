<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * An item of {@see Larder::$bins} keyed by one string the application assigns, blank until
 * written, constructible without arguments, holding an orphan-removing collection of
 * {@see LarderBinNote}s of its own.
 */
#[ORM\Entity]
class LarderBin
{
    #[ORM\ManyToOne(inversedBy: 'bins')]
    public ?Larder $larder = null;

    /**
     * @var Collection<int, LarderBinNote>
     */
    #[ORM\OneToMany(targetEntity: LarderBinNote::class, mappedBy: 'bin', cascade: ['persist'], orphanRemoval: true)]
    public Collection $notes;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(length: 40)]
        public string $code = '',
    ) {
        $this->notes = new ArrayCollection();
    }

    public function addNote(LarderBinNote $note): void
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->bin = $this;
        }
    }

    public function removeNote(LarderBinNote $note): void
    {
        if ($this->notes->removeElement($note)) {
            $note->bin = null;
        }
    }
}

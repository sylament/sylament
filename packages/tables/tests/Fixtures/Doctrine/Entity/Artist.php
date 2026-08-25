<?php

declare(strict_types=1);

namespace Sylament\Tables\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Artist
{
    /**
     * @var Collection<int, Song>
     */
    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'artist')]
    #[ORM\OrderBy(['title' => 'ASC'])]
    private Collection $songs;

    /**
     * @var Collection<int, Album>
     */
    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'artist', orphanRemoval: true)]
    private Collection $albums;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private int $id,
        #[ORM\Column]
        private string $name,
        #[ORM\ManyToOne(targetEntity: Label::class)]
        private ?Label $label = null,
        #[ORM\Embedded(class: Address::class)]
        private Address $address = new Address()
    ) {
        $this->songs = new ArrayCollection();
        $this->albums = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?Label
    {
        return $this->label;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
        }

        $song->setArtist($this);

        return $this;
    }

    public function removeSong(Song $song): static
    {
        $this->songs->removeElement($song);

        if ($song->getArtist() === $this) {
            $song->setArtist(null);
        }

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar-pair identifier beside a prePersist callback that fills neither half,
 * so the incomplete-key check a new record takes before `persist()` exempts the
 * class and only a read after the callback sees a blank half.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Planter
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $bed = '';

    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $slot = '';

    #[ORM\ManyToOne(targetEntity: Greenhouse::class, inversedBy: 'planters')]
    private ?Greenhouse $greenhouse = null;

    #[ORM\Column(length: 255)]
    private string $crop = '';

    #[ORM\Column(length: 40)]
    private string $season = '';

    public function getBed(): string
    {
        return $this->bed;
    }

    public function setBed(string $bed): void
    {
        $this->bed = $bed;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function setSlot(string $slot): void
    {
        $this->slot = $slot;
    }

    public function getGreenhouse(): ?Greenhouse
    {
        return $this->greenhouse;
    }

    public function setGreenhouse(?Greenhouse $greenhouse): void
    {
        $this->greenhouse = $greenhouse;
    }

    public function getCrop(): string
    {
        return $this->crop;
    }

    public function setCrop(string $crop): void
    {
        $this->crop = $crop;
    }

    public function getSeason(): string
    {
        return $this->season;
    }

    #[ORM\PrePersist]
    public function sow(): void
    {
        if ($this->season === '') {
            $this->season = 'spring';
        }
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar-pair identifier beside a prePersist callback that fills neither half.
 * Declaring any prePersist callback exempts a class from the incomplete-key
 * check a new record takes before `persist()`, so what refuses a blank half
 * here is the check that runs after the callback.
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

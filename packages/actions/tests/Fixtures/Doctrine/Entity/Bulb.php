<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single assigned string key beside a prePersist callback that stamps the
 * planting season and never the key, so a record left unkeyed until
 * `persist()` is still unkeyed after it. Doctrine can neither detach nor
 * remove a persisted entity whose only identifier is blank.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Bulb
{
    #[ORM\Id]
    #[ORM\Column(length: 40)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $bloom = '';

    #[ORM\Column(length: 40)]
    private string $season = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBloom(): string
    {
        return $this->bloom;
    }

    public function setBloom(string $bloom): void
    {
        $this->bloom = $bloom;
    }

    public function getSeason(): string
    {
        return $this->season;
    }

    #[ORM\PrePersist]
    public function plant(): void
    {
        if ($this->season === '') {
            $this->season = 'autumn';
        }
    }
}

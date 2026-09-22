<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar-pair identifier beside a plain column that happens to be called
 * `id`: the property a mounted action reads is readable, non-blank and names
 * no record at all. It is also this package's one record keyed by several
 * fields that owns a collection, so a create action can attach into it.
 */
#[ORM\Entity]
class Redirect
{
    /**
     * @var Collection<int, RedirectHop>
     */
    #[ORM\OneToMany(targetEntity: RedirectHop::class, mappedBy: 'redirect')]
    private Collection $hops;

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $host = '';

    #[ORM\Id]
    #[ORM\Column(length: 255)]
    private string $path = '';

    #[ORM\Column]
    private int $id = 0;

    #[ORM\Column(length: 255)]
    private string $target = '';

    public function __construct()
    {
        $this->hops = new ArrayCollection();
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * @return Collection<int, RedirectHop>
     */
    public function getHops(): Collection
    {
        return $this->hops;
    }

    public function addHop(RedirectHop $hop): void
    {
        if (!$this->hops->contains($hop)) {
            $this->hops->add($hop);
        }

        $hop->setRedirect($this);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $name = '';

    /**
     * The owning side of the many-to-many a scalar pair holds the inverse of.
     *
     * @var Collection<int, Redirect>
     */
    #[ORM\ManyToMany(targetEntity: Redirect::class, inversedBy: 'labels')]
    #[ORM\JoinTable(name: 'tag_redirect_link')]
    #[ORM\JoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'redirect_host', referencedColumnName: 'host')]
    #[ORM\InverseJoinColumn(name: 'redirect_path', referencedColumnName: 'path')]
    private Collection $redirects;

    public function __construct()
    {
        $this->redirects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection<int, Redirect>
     */
    public function getRedirects(): Collection
    {
        return $this->redirects;
    }

    public function addRedirect(Redirect $redirect): void
    {
        if (!$this->redirects->contains($redirect)) {
            $this->redirects->add($redirect);
        }
    }

    public function removeRedirect(Redirect $redirect): void
    {
        $this->redirects->removeElement($redirect);
    }
}

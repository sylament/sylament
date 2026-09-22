<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The collection a scalar-pair record owns: the mapped-by to-one is a foreign
 * key onto both of the owner's identifier columns, which is what every
 * one-to-many under such an owner looks like.
 */
#[ORM\Entity]
class RedirectHop
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id = 0;

    #[ORM\Column(length: 255)]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Redirect::class, inversedBy: 'hops')]
    #[ORM\JoinColumn(name: 'redirect_host', referencedColumnName: 'host')]
    #[ORM\JoinColumn(name: 'redirect_path', referencedColumnName: 'path')]
    private ?Redirect $redirect = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    public function setRedirect(?Redirect $redirect): void
    {
        $this->redirect = $redirect;
    }
}

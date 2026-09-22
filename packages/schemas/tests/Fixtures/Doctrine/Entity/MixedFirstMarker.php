<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A composite foreign key whose **first** join column is the nullable one, so a
 * predicate reading `joinColumns[0]` alone calls the association nullable and
 * lets a dissociation through to a NOT NULL violation at flush.
 */
#[ORM\Entity]
class MixedFirstMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Redirect::class, inversedBy: 'firstMixedMarkers')]
    #[ORM\JoinColumn(name: 'redirect_host', referencedColumnName: 'host', nullable: true)]
    #[ORM\JoinColumn(name: 'redirect_path', referencedColumnName: 'path', nullable: false)]
    private ?Redirect $redirect = null;

    public function getId(): ?int
    {
        return $this->id;
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

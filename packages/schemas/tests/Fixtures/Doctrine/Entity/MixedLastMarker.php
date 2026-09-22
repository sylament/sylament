<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * The same mixed nullability in the other column order — refused today only by
 * the accident that its first join column is the NOT NULL one, which is why
 * both orders are fixtures.
 */
#[ORM\Entity]
class MixedLastMarker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Redirect::class, inversedBy: 'lastMixedMarkers')]
    #[ORM\JoinColumn(name: 'redirect_host', referencedColumnName: 'host', nullable: false)]
    #[ORM\JoinColumn(name: 'redirect_path', referencedColumnName: 'path', nullable: true)]
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

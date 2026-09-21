<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * An ordinary single-identifier child of every composite flavour at once, so
 * each of them owns a one-to-many the query builders can be asked for. None of
 * these to-ones is part of this record's identity, so pointing one at another
 * owner moves the record rather than its key.
 */
#[ORM\Entity]
class Marker
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $label = '';

    #[ORM\ManyToOne(targetEntity: Redirect::class, inversedBy: 'markers')]
    #[ORM\JoinColumn(name: 'redirect_host', referencedColumnName: 'host')]
    #[ORM\JoinColumn(name: 'redirect_path', referencedColumnName: 'path')]
    private ?Redirect $redirect = null;

    #[ORM\ManyToOne(targetEntity: PostTranslation::class, inversedBy: 'markers')]
    #[ORM\JoinColumn(name: 'translation_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'translation_locale', referencedColumnName: 'locale')]
    private ?PostTranslation $translation = null;

    #[ORM\ManyToOne(targetEntity: PostSeal::class, inversedBy: 'markers')]
    #[ORM\JoinColumn(name: 'seal_post_id', referencedColumnName: 'post_id')]
    private ?PostSeal $seal = null;

    #[ORM\ManyToOne(targetEntity: Enrolment::class, inversedBy: 'markers')]
    #[ORM\JoinColumn(name: 'enrolment_post_id', referencedColumnName: 'post_id')]
    #[ORM\JoinColumn(name: 'enrolment_tag_id', referencedColumnName: 'tag_id')]
    private ?Enrolment $enrolment = null;

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

    public function getTranslation(): ?PostTranslation
    {
        return $this->translation;
    }

    public function setTranslation(?PostTranslation $translation): void
    {
        $this->translation = $translation;
    }

    public function getSeal(): ?PostSeal
    {
        return $this->seal;
    }

    public function setSeal(?PostSeal $seal): void
    {
        $this->seal = $seal;
    }

    public function getEnrolment(): ?Enrolment
    {
        return $this->enrolment;
    }

    public function setEnrolment(?Enrolment $enrolment): void
    {
        $this->enrolment = $enrolment;
    }
}

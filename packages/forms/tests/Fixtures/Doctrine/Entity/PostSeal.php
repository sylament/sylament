<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A single derived identity — the one identifier field is a to-one
 * association — which Doctrine does not report as composite.
 */
#[ORM\Entity]
class PostSeal
{
    #[ORM\Id]
    #[ORM\OneToOne]
    private ?Post $post = null;

    #[ORM\Column]
    private string $note = '';

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): void
    {
        $this->post = $post;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }
}

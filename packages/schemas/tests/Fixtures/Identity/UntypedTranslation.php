<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Identity;

use Doctrine\ORM\Mapping as ORM;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;

/**
 * A derived identity whose identifier association declares no class, so it
 * can hold a value that is no record. It sits outside the fixture schema's
 * mapped directory: only its metadata is ever read, and no rig stores a row.
 */
#[ORM\Entity]
class UntypedTranslation
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Post::class)]
    private mixed $post = null;

    #[ORM\Id]
    #[ORM\Column(length: 35)]
    private string $locale = '';

    public function setPost(mixed $post): void
    {
        $this->post = $post;
    }

    public function getPost(): mixed
    {
        return $this->post;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar-pair identifier beside a plain column that happens to be called
 * `id`: the property a mounted action reads is readable, non-blank and names
 * no record at all.
 */
#[ORM\Entity]
class Redirect
{
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
}

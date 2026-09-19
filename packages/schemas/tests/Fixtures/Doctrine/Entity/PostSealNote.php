<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A chained derived identity: the identifier association targets an entity
 * whose own identifier is an association.
 */
#[ORM\Entity]
class PostSealNote
{
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'post_id')]
    private ?PostSeal $seal = null;

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code = '';

    #[ORM\Column]
    private string $body = '';

    public function getSeal(): ?PostSeal
    {
        return $this->seal;
    }

    public function setSeal(?PostSeal $seal): void
    {
        $this->seal = $seal;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}

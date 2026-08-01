<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Records;

final class Profile
{
    private string $name = '';

    private ?string $bio = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): void
    {
        $this->bio = $bio;
    }
}

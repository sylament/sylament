<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Records;

use ArrayIterator;

final class WithCollections
{
    /** @var array<int, Profile> */
    private array $profiles = [];

    /** @var ArrayIterator<int, string>|null */
    private ?ArrayIterator $tags = null;

    private ?Profile $vivifying = null;

    /** @return array<int, Profile> */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /** @param array<int, Profile> $profiles */
    public function setProfiles(array $profiles): void
    {
        $this->profiles = $profiles;
    }

    /** @return ArrayIterator<int, string>|null */
    public function getTags(): ?ArrayIterator
    {
        return $this->tags;
    }

    /** @param ArrayIterator<int, string>|null $tags */
    public function setTags(?ArrayIterator $tags): void
    {
        $this->tags = $tags;
    }

    public function getVivifying(): Profile
    {
        return $this->vivifying ??= new Profile();
    }

    public function setVivifying(?Profile $vivifying): void
    {
        $this->vivifying = $vivifying;
    }
}

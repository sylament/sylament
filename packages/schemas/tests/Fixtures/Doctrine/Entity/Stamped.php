<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A scalar pair whose second half the class assigns from its own prePersist
 * callback, which no generator reports and a blank-value test alone would call
 * incomplete.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Stamped
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $scope = '';

    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $stamp = '';

    #[ORM\Column]
    private string $label = '';

    #[ORM\PrePersist]
    public function assignStamp(): void
    {
        if ($this->stamp === '') {
            $this->stamp = 'auto';
        }
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getStamp(): string
    {
        return $this->stamp;
    }

    public function setStamp(string $stamp): void
    {
        $this->stamp = $stamp;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}

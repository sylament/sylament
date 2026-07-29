<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConstrainedField extends Component implements HasValidationConstraints
{
    /** @var list<Constraint> */
    protected array $fixtureConstraints = [];

    public static function make(string $statePath): static
    {
        return new static()->statePath($statePath);
    }

    /** @param list<Constraint> $constraints */
    public function fixtureConstraints(array $constraints): static
    {
        $this->fixtureConstraints = $constraints;

        return $this;
    }

    public function required(): static
    {
        return $this->fixtureConstraints([new NotBlank()]);
    }

    public function getValidationConstraints(): array
    {
        return $this->fixtureConstraints;
    }

    public function dehydrateValidationConstraints(array &$constraints): void
    {
        $statePath = $this->getStatePath();

        if ($statePath === null || $this->getValidationConstraints() === []) {
            return;
        }

        $constraints[$statePath] = $this->getValidationConstraints();
    }
}

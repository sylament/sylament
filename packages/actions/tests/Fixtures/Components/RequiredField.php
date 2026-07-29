<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A constrained state leaf: the actions suite cannot use the real `forms`
 * fields (sibling package, not a dependency of `actions`), so the mounted-action
 * validation pipeline needs its own. Twin of schemas' `ConstrainedField`, fixed
 * at a single unconditional `NotBlank`.
 */
class RequiredField extends Component implements HasValidationConstraints
{
    public static function make(string $statePath): static
    {
        return new static()->statePath($statePath);
    }

    /**
     * @return list<Constraint>
     */
    public function getValidationConstraints(): array
    {
        return [new NotBlank()];
    }

    public function dehydrateValidationConstraints(array &$constraints): void
    {
        $statePath = $this->getStatePath();

        if ($statePath === null) {
            return;
        }

        $constraints[$statePath] = $this->getValidationConstraints();
    }
}

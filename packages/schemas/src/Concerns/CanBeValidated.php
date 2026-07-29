<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Symfony\Component\Validator\Constraint;

trait CanBeValidated
{
    /**
     * @return array<string, list<Constraint>>
     */
    public function getValidationConstraints(): array
    {
        $constraints = [];

        foreach ($this->getComponents(withActions: false, withHidden: true) as $component) {
            if ($component->isNeitherDehydratedNorValidated()) {
                continue;
            }

            if ($component instanceof HasValidationConstraints) {
                $component->dehydrateValidationConstraints($constraints);
            }

            foreach ($component->getChildSchemas() as $childSchema) {
                $constraints = [...$constraints, ...$childSchema->getValidationConstraints()];
            }
        }

        return $constraints;
    }

    public function validate(): void
    {
        if ($this->getValidationConstraints() === []) {
            return;
        }

        $this->getLiveComponent()->validateSchema($this);
    }
}

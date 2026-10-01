<?php

declare(strict_types=1);

namespace Sylament\Schemas\Concerns;

use Closure;
use Sylament\Schemas\Components\Contracts\HasValidationConstraints;
use Symfony\Component\Validator\Constraint;

trait CanBeValidated
{
    protected bool|Closure $isRecordKeyValidated = true;

    /**
     * Whether the record check `getData()` ends in refuses a new record's blank
     * key field and its taken key. Switched off, a blank key field reaches the
     * create seam's own refusal and a taken key the database; a changed key is
     * refused and put back either way.
     */
    public function validateRecordKey(bool|Closure $condition = true): static
    {
        $this->isRecordKeyValidated = $condition;

        return $this;
    }

    public function isRecordKeyValidated(): bool
    {
        return $this->evaluate($this->isRecordKeyValidated);
    }

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

<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use LogicException;
use ReflectionObject;
use ReflectionProperty;
use Sylament\Forms\Components\Contracts\CanBeLengthConstrained;
use Sylament\Forms\Components\Field;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\RecordTarget;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Service\Attribute\Required;

use function Sylament\Support\blank;

trait CanBeValidated
{
    /**
     * Null is "never declared", which is what lets the bound record's write-side
     * types decide; `required(false)` is a declaration and beats them.
     */
    protected bool|Closure|null $isRequired = null;

    /**
     * @var array<array{Constraint|array<Constraint>|Closure|null, bool|Closure}>
     */
    protected array $constraints = [];

    /**
     * @var array<class-string<Constraint>, string|Closure|array<string, string|Closure>>
     */
    protected array $validationMessages = [];

    private RecordTypeReader $recordTypeReader;

    public function required(bool|Closure|null $condition = true): static
    {
        $this->isRequired = $condition;

        return $this;
    }

    public function nullable(bool|Closure $condition = true): static
    {
        $this->required(static fn (Field $component): bool => ! $component->evaluate($condition));

        return $this;
    }

    public function constraint(Constraint|Closure $constraint, bool|Closure $condition = true): static
    {
        $this->constraints[] = [$constraint, $condition];

        return $this;
    }

    /**
     * @param array<Constraint|Closure>|Closure $constraints
     */
    public function constraints(array|Closure $constraints, bool|Closure $condition = true): static
    {
        if ($constraints instanceof Closure) {
            $this->constraints[] = [$constraints, $condition];

            return $this;
        }

        foreach ($constraints as $constraint) {
            $this->constraints[] = [$constraint, $condition];
        }

        return $this;
    }

    /**
     * @param array<class-string<Constraint>, string|Closure|array<string, string|Closure>> $messages
     */
    public function validationMessages(array $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    public function isRequired(): bool
    {
        $declared = $this->evaluate($this->isRequired);

        if ($declared !== null) {
            return (bool) $declared;
        }

        return $this->isRequiredByRecord();
    }

    /**
     * Whether the record's own write-side type makes this field required: a
     * non-nullable target cannot accept the null a blank field dehydrates to.
     * Suppressed under a nullable branch that is currently blank, so an optional
     * nested object stays submittable while empty, and for a field no schema
     * contains, which cannot name its position in the record graph.
     */
    protected function isRequiredByRecord(): bool
    {
        if (! isset($this->container)) {
            return false;
        }

        $recordClass = $this->getRecordClass();

        if ($recordClass === null) {
            return false;
        }

        $recordPath = $this->getRecordStatePath();

        if ($recordPath === null || $this->hasBlankNullableBranchAncestor()) {
            return false;
        }

        $target = $this->getRecordTypeReader()->describe($recordClass, $recordPath);

        return $target instanceof RecordTarget && ! $target->isNullable && ! $target->isCollection;
    }

    private function hasBlankNullableBranchAncestor(): bool
    {
        $ancestor = $this->getContainer()->getParentComponent();

        while ($ancestor instanceof Component) {
            if ($this->isBlankNullableBranch($ancestor)) {
                return true;
            }

            $ancestor = $ancestor->getContainer()->getParentComponent();
        }

        return false;
    }

    private function isBlankNullableBranch(Component $ancestor): bool
    {
        return $ancestor->isNullableBranch() && $ancestor->shouldNullBranch($this->getBranchState($ancestor));
    }

    /**
     * A branch's state read from the live bag, rather than from the dehydrated
     * bag the mapper later passes {@see Component::shouldNullBranch()}:
     * requirement is decided while the form is validated, before any bag is
     * assembled. A branch bound to no live component holds no state at all.
     *
     * @return array<string, mixed>
     */
    private function getBranchState(Component $ancestor): array
    {
        if (! $ancestor->getLiveComponentOrNull() instanceof InteractsWithSchemas) {
            return [];
        }

        $state = $ancestor->getRawState();

        if (! is_array($state)) {
            return [];
        }

        /** @var array<string, mixed> $state */
        return $state;
    }

    /**
     * Message options per constraint class, normalised: a string value becomes the
     * constraint's `message` option. A value resolving to '' is dropped so the
     * constraint keeps its own default.
     *
     * @return array<class-string<Constraint>, array<string, string>>
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->validationMessages as $constraintClass => $options) {
            $evaluated = array_filter(array_map(
                fn (string|Closure $message): string => $this->evaluate($message),
                is_array($options) ? $options : ['message' => $options],
            ));

            if ($evaluated !== []) {
                $messages[$constraintClass] = $evaluated;
            }
        }

        return $messages;
    }

    /**
     * @return list<Constraint>
     */
    public function getValidationConstraints(): array
    {
        return $this->applyValidationMessages($this->buildValidationConstraints());
    }

    /**
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = [];

        if ($this->isRequired()) {
            $constraints[] = $this->getRequiredConstraint();
        }

        if ($this instanceof CanBeLengthConstrained) {
            $constraints = [...$constraints, ...$this->getLengthValidationConstraints()];
        }

        foreach ($this->constraints as [$constraint, $condition]) {
            if (! $this->evaluate($condition)) {
                continue;
            }

            $evaluated = $this->evaluate($constraint);

            if ($evaluated === null) {
                continue;
            }

            foreach ($evaluated instanceof Constraint ? [$evaluated] : $evaluated as $single) {
                $constraints[] = $single;
            }
        }

        return $constraints;
    }

    /**
     * @param  list<Constraint> $constraints
     * @return list<Constraint>
     */
    protected function applyValidationMessages(array $constraints): array
    {
        $messages = $this->getValidationMessages();

        if ($messages === []) {
            return $constraints;
        }

        foreach ($constraints as $index => $constraint) {
            $options = $messages[$constraint::class] ?? [];

            if ($options === []) {
                continue;
            }

            $available = $this->getValidationMessageOptions($constraint);
            $constraint = clone $constraint;
            $constraints[$index] = $constraint;

            foreach ($options as $option => $message) {
                if (! in_array($option, $available, strict: true)) {
                    throw new LogicException(sprintf(
                        'Sylament cannot apply a validation message to [%s] because %s has no [%s] message option. Use one of: [%s].',
                        $this->getStatePath(),
                        $constraint::class,
                        $option,
                        implode('], [', $available),
                    ));
                }

                $constraint->{$option} = $message;
            }
        }

        return $constraints;
    }

    /**
     * @return list<string>
     */
    private function getValidationMessageOptions(Constraint $constraint): array
    {
        $options = [];

        foreach (new ReflectionObject($constraint)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($name === 'message' || str_ends_with($name, 'Message')) {
                $options[] = $name;
            }
        }

        return $options;
    }

    protected function getRequiredConstraint(): Constraint
    {
        return new NotBlank();
    }

    /**
     * @param array<string, list<Constraint>> $constraints
     */
    public function dehydrateValidationConstraints(array &$constraints): void
    {
        $statePath = $this->getStatePath();

        if (blank($statePath)) {
            return;
        }

        $componentConstraints = $this->getValidationConstraints();

        if ($componentConstraints === []) {
            return;
        }

        $constraints[$statePath] = $componentConstraints;
    }

    #[Required]
    public function setRecordTypeReader(RecordTypeReader $recordTypeReader): void
    {
        $this->recordTypeReader = $recordTypeReader;
    }

    public function getRecordTypeReader(): RecordTypeReader
    {
        return $this->recordTypeReader ??= new RecordTypeReader();
    }
}

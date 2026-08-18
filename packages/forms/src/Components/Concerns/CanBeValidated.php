<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use DateTimeImmutable;
use Exception;
use LogicException;
use ReflectionObject;
use ReflectionProperty;
use Sylament\Forms\Components\Contracts\CanBeLengthConstrained;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Validation\DateComparison;
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

    public function after(string|Closure $date, bool $isStatePathAbsolute = false): static
    {
        return $this->dateComparisonRule(DateComparison::AFTER, $date, $isStatePathAbsolute);
    }

    public function afterOrEqual(string|Closure $date, bool $isStatePathAbsolute = false): static
    {
        return $this->dateComparisonRule(DateComparison::AFTER_OR_EQUAL, $date, $isStatePathAbsolute);
    }

    public function before(string|Closure $date, bool $isStatePathAbsolute = false): static
    {
        return $this->dateComparisonRule(DateComparison::BEFORE, $date, $isStatePathAbsolute);
    }

    public function beforeOrEqual(string|Closure $date, bool $isStatePathAbsolute = false): static
    {
        return $this->dateComparisonRule(DateComparison::BEFORE_OR_EQUAL, $date, $isStatePathAbsolute);
    }

    /**
     * @param string $rule one of the {@see DateComparison} operator constants,
     *        which carry Filament's rule-string spellings
     */
    public function dateComparisonRule(string $rule, string|Closure $date, bool $isStatePathAbsolute = false): static
    {
        $this->constraint(
            fn (): ?DateComparison => $this->buildDateComparisonConstraint($rule, $date, $isStatePathAbsolute),
            fn (): bool => (bool) $this->evaluate($date),
        );

        return $this;
    }

    /**
     * The boundary is resolved per validation run: a value `strtotime()` reads is
     * the literal itself, anything else names another field, whose live raw state
     * the root container reads exactly as the runner does. A boundary that cannot
     * be resolved or parsed drops the constraint rather than failing the field —
     * the named field's own `required()` owns its presence. A blank declaration
     * never reaches here at all: the pair's condition drops it first.
     */
    protected function buildDateComparisonConstraint(string $operator, string|Closure $date, bool $isStatePathAbsolute): ?DateComparison
    {
        $date = $this->evaluate($date);

        if (! strtotime($date)) {
            $liveComponent = $this->getLiveComponentOrNull();

            if (! $liveComponent instanceof InteractsWithSchemas) {
                return null;
            }

            $date = $this->getRootContainer()->getPropertyAccessor()->get(
                $liveComponent,
                $this->resolveRelativeStatePath($date, $isStatePathAbsolute),
            );

            if (! is_string($date) || mb_trim($date) === '') {
                return null;
            }
        }

        try {
            $boundary = new DateTimeImmutable($date);
        } catch (Exception) {
            // An unresolvable boundary is no comparison at all.
            return null;
        }

        return new DateComparison($operator, $boundary);
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
     * contains, which cannot name its position in the record graph. The class is
     * read from the root container — the frame the record-relative path indexes
     * and the frame `getData()` maps in — so a component-tier `record()` binding
     * feeds closure injection, never derivation.
     */
    protected function isRequiredByRecord(): bool
    {
        if (! isset($this->container)) {
            return false;
        }

        $recordClass = $this->getRootContainer()->getRecordClass();

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

    /**
     * A component carrying no record state path is never a branch, however it is
     * declared — it names no property to null, which is why the mapper refuses
     * the declaration outright. Both walks have to agree on that: without the
     * gate, {@see getBranchState()} would fall through to the container's own
     * path and judge blankness against the whole bag, making one field's
     * requirement depend on unrelated fields elsewhere in the form.
     */
    private function isBlankNullableBranch(Component $ancestor): bool
    {
        if (! $ancestor->isNullableBranch() || $ancestor->getRecordStatePath() === null) {
            return false;
        }

        return $ancestor->shouldNullBranch($this->getBranchState($ancestor));
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

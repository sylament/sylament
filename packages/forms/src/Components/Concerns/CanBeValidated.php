<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use DateTimeImmutable;
use Exception;
use LogicException;
use ReflectionObject;
use ReflectionProperty;
use Sylament\Forms\Components\Contracts\CanBeLengthConstrained;
use Sylament\Forms\Components\Field;
use Sylament\Forms\Validation\DateComparison;
use Sylament\Forms\Validation\Distinct;
use Sylament\Forms\Validation\MatchesField;
use Sylament\Forms\Validation\Prohibited;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\State\RecordTarget;
use Sylament\Schemas\State\RecordTypeReader;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\DivisibleBy;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Service\Attribute\Required;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

trait CanBeValidated
{
    /**
     * Laravel's `accepted` list, matched strictly.
     *
     * @var list<mixed>
     */
    private const array ACCEPTED_VALUES = ['yes', 'on', '1', 1, true, 'true'];

    /**
     * Laravel's `declined` list, matched strictly.
     *
     * @var list<mixed>
     */
    private const array DECLINED_VALUES = ['no', 'off', '0', 0, false, 'false'];

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
            static fn (Field $component): ?DateComparison => $component->buildDateComparisonConstraint($rule, $date, $isStatePathAbsolute),
            static fn (Field $component): bool => (bool) $component->evaluate($date),
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
            if (! $this->hasLiveSiblingState()) {
                return null;
            }

            $date = $this->resolveSiblingRawState($date, $isStatePathAbsolute);

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

    public function gt(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->orderingComparisonRule(GreaterThan::class, $statePath, $isStatePathAbsolute);
    }

    public function gte(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->orderingComparisonRule(GreaterThanOrEqual::class, $statePath, $isStatePathAbsolute);
    }

    public function lt(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->orderingComparisonRule(LessThan::class, $statePath, $isStatePathAbsolute);
    }

    public function lte(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->orderingComparisonRule(LessThanOrEqual::class, $statePath, $isStatePathAbsolute);
    }

    public function same(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->matchingComparisonRule($statePath, $isStatePathAbsolute, match: true);
    }

    public function different(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->matchingComparisonRule($statePath, $isStatePathAbsolute, match: false);
    }

    /**
     * Laravel's `confirmed`: the partner is a real component in the same container
     * named `<this field>_confirmation`, read absolutely because the suffix applies
     * to the whole dotted path.
     */
    public function confirmed(bool|Closure $condition = true): static
    {
        $this->constraint(
            static fn (Field $component): ?MatchesField => $component->buildMatchesFieldConstraint($component->getStatePath() . '_confirmation', isStatePathAbsolute: true, match: true),
            $condition,
        );

        return $this;
    }

    /**
     * @param class-string<GreaterThan|GreaterThanOrEqual|LessThan|LessThanOrEqual> $constraintClass
     */
    private function orderingComparisonRule(string $constraintClass, string|Closure $statePath, bool $isStatePathAbsolute): static
    {
        $this->constraint(
            static function (Field $component) use ($constraintClass, $statePath, $isStatePathAbsolute): ?Constraint {
                if (! $component->hasLiveSiblingState()) {
                    return null;
                }

                $sibling = $component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute);

                // A boundary that is not there is no comparison at all — the named
                // field's own `required()` owns its presence, exactly as the date
                // family already decided.
                if ($sibling === null || (is_string($sibling) && mb_trim($sibling) === '')) {
                    return null;
                }

                return new $constraintClass(value: $sibling);
            },
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
        );

        return $this;
    }

    private function matchingComparisonRule(string|Closure $statePath, bool $isStatePathAbsolute, bool $match): static
    {
        $this->constraint(
            static fn (Field $component): ?MatchesField => $component->buildMatchesFieldConstraint((string) $component->evaluate($statePath), $isStatePathAbsolute, $match),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
        );

        return $this;
    }

    /**
     * Unlike the ordering arms, a blank or null partner is compared rather than
     * skipped: an untyped confirmation field is the case the rule exists for.
     */
    private function buildMatchesFieldConstraint(string $statePath, bool $isStatePathAbsolute, bool $match): ?MatchesField
    {
        if (! $this->hasLiveSiblingState()) {
            return null;
        }

        return new MatchesField(
            value: $this->resolveSiblingRawState($statePath, $isStatePathAbsolute),
            other: $this->getSiblingLabel($this->resolveRelativeStatePath($statePath, $isStatePathAbsolute)),
            match: $match,
        );
    }

    /**
     * The partner's own label, so a message names it instead of printing its value;
     * the path's last segment humanised when no component answers to that path.
     */
    private function getSiblingLabel(string $absoluteStatePath): string
    {
        $sibling = $this->getRootContainer()->getComponentByStatePath($absoluteStatePath);
        $label = $sibling instanceof Field ? $sibling->getLabel() : null;

        if (filled($label)) {
            return strip_tags((string) $label);
        }

        $segment = mb_substr($absoluteStatePath, (int) mb_strrpos($absoluteStatePath, '.') + (str_contains($absoluteStatePath, '.') ? 1 : 0));

        return ucfirst(str_replace('_', ' ', $segment));
    }

    public function requiredIf(string|Closure $statePath, mixed $stateValues, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => $component->matchesConditionValues($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), $component->evaluate($stateValues)),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: false,
        );
    }

    public function requiredUnless(string|Closure $statePath, mixed $stateValues, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => ! $component->matchesConditionValues($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), $component->evaluate($stateValues)),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: false,
        );
    }

    public function requiredIfAccepted(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => in_array($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), self::ACCEPTED_VALUES, strict: true),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: false,
        );
    }

    public function requiredIfDeclined(string|Closure $statePath, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => in_array($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), self::DECLINED_VALUES, strict: true),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: false,
        );
    }

    /**
     * @param string|array<string>|Closure $statePaths
     */
    public function requiredWith(string|array|Closure $statePaths, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => array_any($component->resolveSiblingStatePaths($statePaths, $isStatePathAbsolute), static fn (string $path): bool => filled($component->resolveSiblingRawState($path, isStatePathAbsolute: true))),
            static fn (Field $component): bool => (bool) $component->evaluate($statePaths),
            isProhibition: false,
        );
    }

    /**
     * @param string|array<string>|Closure $statePaths
     */
    public function requiredWithAll(string|array|Closure $statePaths, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => array_all($component->resolveSiblingStatePaths($statePaths, $isStatePathAbsolute), static fn (string $path): bool => filled($component->resolveSiblingRawState($path, isStatePathAbsolute: true))),
            static fn (Field $component): bool => (bool) $component->evaluate($statePaths),
            isProhibition: false,
        );
    }

    /**
     * @param string|array<string>|Closure $statePaths
     */
    public function requiredWithout(string|array|Closure $statePaths, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => array_any($component->resolveSiblingStatePaths($statePaths, $isStatePathAbsolute), static fn (string $path): bool => blank($component->resolveSiblingRawState($path, isStatePathAbsolute: true))),
            static fn (Field $component): bool => (bool) $component->evaluate($statePaths),
            isProhibition: false,
        );
    }

    /**
     * @param string|array<string>|Closure $statePaths
     */
    public function requiredWithoutAll(string|array|Closure $statePaths, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => array_all($component->resolveSiblingStatePaths($statePaths, $isStatePathAbsolute), static fn (string $path): bool => blank($component->resolveSiblingRawState($path, isStatePathAbsolute: true))),
            static fn (Field $component): bool => (bool) $component->evaluate($statePaths),
            isProhibition: false,
        );
    }

    public function prohibited(bool|Closure $condition = true): static
    {
        $this->constraint(new Prohibited(), $condition);

        return $this;
    }

    public function prohibitedIf(string|Closure $statePath, mixed $stateValues, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => $component->matchesConditionValues($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), $component->evaluate($stateValues)),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: true,
        );
    }

    public function prohibitedUnless(string|Closure $statePath, mixed $stateValues, bool $isStatePathAbsolute = false): static
    {
        return $this->conditionalPresenceRule(
            static fn (Field $component): bool => ! $component->matchesConditionValues($component->resolveSiblingRawState((string) $component->evaluate($statePath), $isStatePathAbsolute), $component->evaluate($stateValues)),
            static fn (Field $component): bool => (bool) $component->evaluate($statePath),
            isProhibition: true,
        );
    }

    /**
     * Laravel puts the violation on *this* field: if I am present, they must be
     * empty. The offending siblings' labels are baked into the message at build
     * time, so the constraint stays a plain presence test.
     *
     * @param array<string>|string|Closure $statePaths
     */
    public function prohibits(array|string|Closure $statePaths, bool $isStatePathAbsolute = false): static
    {
        $this->constraint(
            static function (Field $component) use ($statePaths, $isStatePathAbsolute): ?Prohibited {
                if (! $component->hasLiveSiblingState()) {
                    return null;
                }

                $filled = array_values(array_filter(
                    $component->resolveSiblingStatePaths($statePaths, $isStatePathAbsolute),
                    static fn (string $path): bool => filled($component->resolveSiblingRawState($path, isStatePathAbsolute: true)),
                ));

                if ($filled === []) {
                    return null;
                }

                return new Prohibited(message: sprintf(
                    'This value cannot be combined with %s.',
                    implode(', ', array_map($component->getSiblingLabel(...), $filled)),
                ));
            },
            static fn (Field $component): bool => (bool) $component->evaluate($statePaths),
        );

        return $this;
    }

    public function filled(bool|Closure $condition = true): static
    {
        $this->constraint(static fn (Field $component): Constraint => $component->getRequiredConstraint(), $condition);

        return $this;
    }

    public function multipleOf(int|float|Closure $value): static
    {
        $this->constraint(static function (Field $component) use ($value): ?DivisibleBy {
            $divisor = $component->evaluateDivisor($value);

            return $divisor === null ? null : new DivisibleBy(value: $divisor);
        });

        return $this;
    }

    /**
     * The divisor, or null when the closure arm resolved to nothing — which is what
     * Filament's `filled()` pair condition is testing for. The parameter is `mixed`
     * because that is what a closure may answer, whatever the setter's union says.
     */
    private function evaluateDivisor(mixed $value): int|float|null
    {
        $divisor = $this->evaluate($value);

        return is_int($divisor) || is_float($divisor) ? $divisor : null;
    }

    /**
     * Filament's `distinct()`, which compares one repeater item's field against the
     * same field in every sibling item. The three arms are the value's PHP type;
     * the sibling facts are resolved here and handed to the constraint.
     */
    public function distinct(bool|Closure $condition = true): static
    {
        $this->constraint(static fn (Field $component): ?Distinct => $component->buildDistinctConstraint(), $condition);

        return $this;
    }

    private function buildDistinctConstraint(): ?Distinct
    {
        if (! $this->hasLiveSiblingState()) {
            return null;
        }

        $rawState = $this->getRawState();

        if (blank($rawState)) {
            return null;
        }

        $repeater = $this->getParentRepeater();
        $item = $this->getParentRepeaterItem();
        $statePath = $this->getStatePath();

        if ($repeater === null || $item === null || $statePath === null) {
            return null;
        }

        $itemPath = (string) $item->getStatePath();
        $itemKey = (string) $item->getStatePath(isAbsolute: false);

        // The field's path *within* an item, taken by stripping the item schema's own
        // prefix rather than by string surgery on the first dot — an item key may hold
        // one, and a layout component between the two contributes no segment either way.
        $fieldPath = mb_substr($statePath, mb_strlen($itemPath) + 1);
        $itemsPrefix = mb_substr($itemPath, 0, mb_strlen($itemPath) - mb_strlen($itemKey));
        $siblingKeys = array_keys(array_diff_key(wrap($repeater->getRawState()), [$itemKey => true]));

        if ($siblingKeys === []) {
            return null;
        }

        $siblingValues = array_map(
            fn (int|string $siblingKey): mixed => $this->resolveSiblingRawState("{$itemsPrefix}{$siblingKey}.{$fieldPath}", isStatePathAbsolute: true),
            $siblingKeys,
        );

        if (is_bool($rawState)) {
            return new Distinct(
                mode: Distinct::BOOLEAN,
                isSiblingSelected: in_array(true, $siblingValues, strict: true),
                mustBeSelected: $this->isRequired(),
            );
        }

        if (is_array($rawState)) {
            $union = [];

            foreach ($siblingValues as $siblingValue) {
                $union = [...$union, ...array_values(wrap($siblingValue))];
            }

            return new Distinct(mode: Distinct::ARRAY, siblingValues: $union);
        }

        return new Distinct(mode: Distinct::SCALAR, siblingValues: $siblingValues);
    }

    /**
     * A conditional presence rule: the field's own required constraint when the
     * predicate holds, or {@see Prohibited} for the prohibition twin. Nothing is
     * emitted for a field that is already required — the base builder has put that
     * constraint in the list once, and a second copy reports the message twice.
     * `$isRequired` is never written, so no asterisk appears; a conditionally
     * required field that should be starred says `markAsRequired()`.
     *
     * `$predicate` receives the evaluating field, never the one this method was
     * called on: a repeater clones its item components, and a closure that captured
     * `$this` would answer for the template rather than the item.
     */
    private function conditionalPresenceRule(Closure $predicate, bool|Closure $condition, bool $isProhibition): static
    {
        $this->constraint(
            static function (Field $component) use ($predicate, $isProhibition): ?Constraint {
                if (! $component->hasLiveSiblingState()) {
                    return null;
                }

                if (! $isProhibition && $component->isRequired()) {
                    return null;
                }

                if (! $predicate($component)) {
                    return null;
                }

                return $isProhibition ? new Prohibited() : $component->getRequiredConstraint();
            },
            $condition,
        );

        return $this;
    }

    /**
     * Laravel's exact strictness rule: loose for strings and ints, so a text input's
     * `'1'` still matches `1`, but strict for booleans and nulls, so a checkbox's raw
     * `true` is not matched by the string `'1'`.
     */
    private function matchesConditionValues(mixed $other, mixed $values): bool
    {
        // Not `wrap()`: its `null` → `[]` arm reads null as absence, and here null is a
        // value the caller asked to match — `requiredIf('other', null)` means "when the
        // sibling is null", which the live bag cannot distinguish from an absent key.
        $values = array_map(
            static fn (mixed $value): mixed => $value instanceof BackedEnum ? $value->value : $value,
            is_array($values) ? $values : [$values],
        );

        return in_array($other, $values, strict: is_bool($other) || $other === null);
    }

    /**
     * Filament's `multiFieldComparisonRule()` parsing, absolute: an array as-is, a
     * comma string split and trimmed *only* when the paths are relative — an
     * absolute string passes through unsplit.
     *
     * @param  string|array<string>|Closure $statePaths
     * @return list<string>
     */
    private function resolveSiblingStatePaths(string|array|Closure $statePaths, bool $isStatePathAbsolute): array
    {
        $statePaths = $this->evaluate($statePaths);

        if (is_string($statePaths) && ! $isStatePathAbsolute) {
            $statePaths = array_map(mb_trim(...), explode(',', $statePaths));
        }

        return array_values(array_map(
            fn (mixed $statePath): string => $this->resolveRelativeStatePath((string) $statePath, $isStatePathAbsolute),
            wrap($statePaths),
        ));
    }

    /**
     * Whether sibling state can be read at all: the field sits in a schema bound to
     * a live host. A unit-built field, or a schema rendered without one, emits no
     * cross-field constraint — there is nothing to read.
     */
    private function hasLiveSiblingState(): bool
    {
        return $this->getLiveComponentOrNull() instanceof InteractsWithSchemas;
    }

    /**
     * The raw live state at another field's path, container-relative unless
     * absolute — the read the runner itself performs. Re-run on every build, so a
     * sticky re-validation tracks a sibling the user is still typing into.
     */
    protected function resolveSiblingRawState(string $statePath, bool $isStatePathAbsolute = false): mixed
    {
        $liveComponent = $this->getLiveComponentOrNull();

        if (! $liveComponent instanceof InteractsWithSchemas) {
            // Named by `getName()`, never by `getStatePath()`: the absolute path walks
            // `getContainer()`, an uninitialised typed property on exactly the unbound
            // field this branch describes.
            throw new LogicException(sprintf(
                '[%s] cannot read sibling state: it sits in no schema bound to a live component.',
                $this->getName() ?? static::class,
            ));
        }

        return $this->getRootContainer()->getPropertyAccessor()->get(
            $liveComponent,
            $this->resolveRelativeStatePath($statePath, $isStatePathAbsolute),
        );
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

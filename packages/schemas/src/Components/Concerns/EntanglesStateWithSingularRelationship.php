<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use LogicException;
use ReflectionClass;
use Sylament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Sylament\Schemas\State\AssociationTarget;
use Symfony\Component\PropertyAccess\Exception\AccessException;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

/**
 * Entangle a layout component's subtree with a to-one Doctrine association: the
 * component takes the association's property as its state path and maps that
 * path wholesale, so the fields nested inside it read off the related record and
 * write back onto it rather than onto the record the schema is bound to.
 */
trait EntanglesStateWithSingularRelationship
{
    protected ?string $relationship = null;

    protected bool|Closure $hasRelationship = false;

    /**
     * Back this component's subtree with a to-one association: `$name` names the
     * association property and becomes the component's state path, so its fields
     * nest under the relation name. `$condition` false keeps the fields on screen
     * but turns the relationship off — the save then deletes the related record
     * instead of writing to it.
     *
     * The name is resolved once, here, because it is also the state path the
     * whole tree is built from; a closure naming it is evaluated at declaration
     * rather than per read.
     *
     * @throws LogicException when the relationship names no property — a layout
     *                        component carries no name to fall back on
     */
    public function relationship(string|Closure|null $name = null, bool|Closure $condition = true): static
    {
        $name = $this->evaluate($name);

        if (blank($name)) {
            throw new LogicException(sprintf(
                '%s declares relationship(), but names no association property to entangle with — a layout component carries no name of its own to fall back on. Pass the property Doctrine maps as relationship()\'s first argument.',
                static::class,
            ));
        }

        $this->relationship = $name;
        $this->hasRelationship = $condition;

        $this->statePath($name);

        $this->loadStateFromRelationshipsUsing(static fn (CanEntangleWithSingularRelationships $component, object $record): array => $component->getStateFromRelationship($record));

        $this->saveRelationshipsUsing(static function (CanEntangleWithSingularRelationships $component, object $record, mixed $state): void {
            $component->saveStateToRelationship($record, $state);
        });

        return $this;
    }

    public function getRelationshipName(): ?string
    {
        return $this->relationship;
    }

    /**
     * Whether the relationship is on — declared, and its condition true. A
     * declared relationship whose condition is false still owns its subtree and
     * still fills; only the save turns into a delete.
     */
    public function hasRelationship(): bool
    {
        return $this->evaluate($this->hasRelationship) && filled($this->relationship);
    }

    public function ownsRecordSubtree(): bool
    {
        return filled($this->relationship) || parent::ownsRecordSubtree();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStateFromRelationship(object $record): array
    {
        $this->getSingularRelationshipTarget();

        $accessor = $this->getPropertyAccessor();
        $related = $accessor->getFromRecord($record, $this->getRelationshipNameOrFail());

        if (! is_object($related)) {
            return [];
        }

        $state = [];

        foreach ($this->getRelatedStatePaths() as $statePath) {
            try {
                $value = $accessor->getFromRecord($related, $statePath);
            } catch (AccessException) {
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $accessor->setInArray($state, $statePath, $value);
        }

        return $state;
    }

    /**
     * Map the submitted subtree back onto the related record: the one the record
     * already holds is mutated in place, so its identity — and everything else
     * pointing at it — survives the save; a record holding none takes a freshly
     * constructed one, which the association's cascade persists. A relationship
     * the condition turned off nulls the property and removes the record that was
     * there, so an entanglement switched off deletes rather than orphans.
     *
     * @throws LogicException when the property cannot be nulled, or when the
     *                        association's target cannot be built without
     *                        arguments
     */
    public function saveStateToRelationship(object $record, mixed $state): void
    {
        $target = $this->getSingularRelationshipTarget();
        $name = $this->getRelationshipNameOrFail();
        $accessor = $this->getPropertyAccessor();

        $existing = $accessor->getFromRecord($record, $name);
        $existing = is_object($existing) ? $existing : null;

        if (! $this->hasRelationship()) {
            if ($existing !== null) {
                $this->assertRelationshipAcceptsNull($record, $name);

                $accessor->setOnRecord($record, $name, null);
                $this->getAssociationReader()->remove($existing);
            }

            return;
        }

        $related = $existing ?? $this->constructRelatedRecord($target->targetClass);

        /** @var array<string, mixed> $state */
        $state = is_array($state) ? $state : [];

        foreach ($this->getRelatedStatePaths() as $statePath) {
            if ($accessor->hasInArray($state, $statePath)) {
                $accessor->setOnRecord($related, $statePath, $accessor->getFromArray($state, $statePath));
            }
        }

        if ($existing === null) {
            $accessor->setOnRecord($record, $name, $related);
        }
    }

    /**
     * The dotted state paths this component's subtree declares, relative to the
     * related record — what a fill reads off it and a save writes back onto it.
     *
     * @return list<string>
     */
    private function getRelatedStatePaths(): array
    {
        $prefix = $this->getStatePath() . '.';
        $statePaths = [];

        foreach ($this->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getFlatComponents(withActions: false) as $component) {
                $statePath = $component->getStatePath();

                if (($statePath !== null) && str_starts_with($statePath, $prefix)) {
                    $statePaths[] = mb_substr($statePath, mb_strlen($prefix));
                }
            }
        }

        return $statePaths;
    }

    /**
     * What the mapping declares at the entangled property, refusing everything
     * this component cannot map: an association it cannot resolve, or one holding
     * a collection rather than a single record.
     *
     * @throws LogicException when the relationship cannot be resolved to a to-one
     *                        association
     */
    private function getSingularRelationshipTarget(): AssociationTarget
    {
        $name = $this->getRelationshipNameOrFail();
        $recordClass = $this->getRecordClass();
        $reader = $this->getAssociationReader();

        $target = ($recordClass === null) ? null : $reader->describe($recordClass, $name);

        if (! $target instanceof AssociationTarget) {
            throw new LogicException(sprintf(
                '%s declares relationship("%s"), but %s %s',
                static::class,
                $name,
                match (true) {
                    ! $reader->hasEntityManager() => 'no Doctrine EntityManager backs it, so the related record could neither be read nor written. Register Doctrine (doctrine/doctrine-bundle),',
                    $recordClass === null => 'the schema names no record class to read the association off. Give the schema a record() instance or class-string,',
                    default => sprintf('[%s] maps no association at "%s". Name the property Doctrine maps,', $recordClass, $name),
                },
                $this->getRelationshipFallbackAdvice(),
            ));
        }

        if ($target->isToMany) {
            throw new LogicException(sprintf(
                '%s declares relationship("%s"), but that association holds a collection of %s rather than a single record, so one set of fields could not map it. Use a Repeater for a to-many association, %s',
                static::class,
                $name,
                $target->targetClass,
                $this->getRelationshipFallbackAdvice(),
            ));
        }

        return $target;
    }

    private function getRelationshipFallbackAdvice(): string
    {
        return 'or drop relationship() and map the related record by hand after getData().';
    }

    /**
     * @throws LogicException when the component is asked for its relationship
     *                        while declaring none
     */
    private function getRelationshipNameOrFail(): string
    {
        return $this->relationship ?? throw new LogicException(sprintf(
            '%s was asked for its relationship, but relationship() declared none.',
            static::class,
        ));
    }

    /**
     * @param class-string $targetClass
     *
     * @throws LogicException when the association's target cannot be built
     *                        without arguments
     */
    private function constructRelatedRecord(string $targetClass): object
    {
        $reflection = new ReflectionClass($targetClass);
        $constructor = $reflection->getConstructor();

        if (($constructor !== null) && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new LogicException(sprintf(
                'Cannot map state path "%s" onto a new %s: it requires constructor arguments, so the mapper cannot build it. Give the constructor defaults, or drop relationship() and create the related record by hand after getData().',
                (string) $this->getStatePath(),
                $targetClass,
            ));
        }

        return $reflection->newInstance();
    }

    /**
     * @throws LogicException when the entangled property rejects the null a
     *                        switched-off relationship writes
     */
    private function assertRelationshipAcceptsNull(object $record, string $name): void
    {
        if ($this->getPropertyAccessor()->acceptsNullOnRecord($record::class, $name)) {
            return;
        }

        throw new LogicException(sprintf(
            'Cannot switch off relationship("%s") on %s: %s declares that property non-nullable, so the related record could not be detached before it is deleted. Make the property nullable, or drop the relationship() condition.',
            $name,
            static::class,
            $record::class,
        ));
    }
}

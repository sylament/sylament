<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use LogicException;
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
 *
 * The association itself — the name, the target and the refusals — comes from
 * {@see BelongsToRelationship}, shared with the relation-backed fields.
 */
trait EntanglesStateWithSingularRelationship
{
    use BelongsToRelationship;

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

        $related = $existing ?? $accessor->constructRecord(
            $target->targetClass,
            sprintf('state path "%s" onto a new related record', (string) $this->getStatePath()),
            'drop relationship() and create the related record by hand after getData().',
        );

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

    protected function getMissingEntityManagerConsequence(): string
    {
        return 'the related record could neither be read nor written';
    }

    protected function getRelationshipFallbackAdvice(): string
    {
        return 'or drop relationship() and map the related record by hand after getData().';
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
     * What the mapping declares at the entangled property, refusing the one shape
     * {@see BelongsToRelationship::getRelationshipTarget()} allows and this
     * component cannot map: an association holding a collection rather than a
     * single record.
     *
     * @throws LogicException when the relationship cannot be resolved to a to-one
     *                        association
     */
    private function getSingularRelationshipTarget(): AssociationTarget
    {
        $target = $this->getRelationshipTarget();

        if ($target->isToMany) {
            throw new LogicException(sprintf(
                '%s declares relationship("%s"), but that association holds a collection of %s rather than a single record, so one set of fields could not map it. Use a Repeater for a to-many association, %s',
                $this->getRelationshipSubject(),
                $this->getRelationshipNameOrFail(),
                $target->targetClass,
                $this->getRelationshipFallbackAdvice(),
            ));
        }

        return $target;
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

<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\CanEntangleWithSingularRelationships;
use Sylament\Schemas\Schema;
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
 *
 * The subtree is re-rooted onto the related record and the relationship hooks
 * declared inside it are routed there too: the record path map drops every path
 * beneath a subtree owner, so a nested component's hook only fires because this
 * component takes delegation of it
 * ({@see \Sylament\Schemas\Concerns\BelongsToRecord::getNestedRelationshipHooks()}).
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

    public function routesNestedRelationshipHookAt(string $path): bool
    {
        return filled($this->relationship)
            && array_key_exists($path, $this->getMergedNestedRelationshipHooks());
    }

    /**
     * Every child schema is re-rooted onto the record this component maps, so a
     * component nested inside reads the *related* record — what its closures are
     * handed, and what its own relationship target resolves against. Decoration
     * keys are stamped with the rest: the leaf walks below consult every child
     * schema, so the two would otherwise disagree about which record a path
     * belongs to.
     *
     * The binding is a closure floored at the association's target class rather
     * than at null, because a null record falls through to the parent — an
     * unbound branch would hand the subtree the outer record, the one record it
     * must never see. Resolving that class is deliberately soft: the stamp is
     * evaluated from structural reads, and a refusal belongs on the fill and save
     * edges rather than in every tree walk.
     */
    public function getChildSchema(?string $key = null): ?Schema
    {
        $schema = parent::getChildSchema($key);

        if (($schema === null) || ! filled($this->relationship)) {
            return $schema;
        }

        return $schema->record(fn (): object|string|null => $this->getRelatedRecord() ?? $this->getSoftRelationshipTargetClass());
    }

    /**
     * Read the subtree off the related record: the paths a nested relationship
     * hook loads come from that hook, everything else from the declared leaves,
     * an object-valued leaf only where the reading component's own state casts
     * flatten it ({@see Component::isObjectCarriedByStateCasts()}). A load-hooked
     * path leaves the leaf read exactly as
     * {@see \Sylament\Schemas\Concerns\BelongsToRecord::readStateFromRecord()}
     * skips it at the root — a save-only hook keeps its ordinary read.
     *
     * @return array<string, mixed>
     */
    public function getStateFromRelationship(object $record): array
    {
        $this->requireSingularRelationshipTarget();

        $accessor = $this->getPropertyAccessor();
        $related = $accessor->getFromRecord($record, $this->getRelationshipNameOrFail());

        if (! is_object($related)) {
            return [];
        }

        $hooks = array_filter(
            $this->getMergedNestedRelationshipHooks(),
            static fn (Component $component): bool => $component->hasLoadStateFromRelationships(),
        );

        $state = [];

        foreach ($hooks as $statePath => $component) {
            $accessor->setInArray($state, $statePath, $component->loadStateFromRelationships($related));
        }

        $claimedStatePaths = array_keys($hooks);

        foreach ($this->getRelatedStateComponents() as $statePath => $component) {
            $statePath = (string) $statePath;

            if ($this->isClaimedByNestedHook($statePath, $claimedStatePaths)) {
                continue;
            }

            try {
                $value = $accessor->getFromRecord($related, $statePath);
            } catch (AccessException) {
                continue;
            }

            if (is_object($value) && ! $component->isObjectCarriedByStateCasts($value)) {
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
     * there, so an entanglement switched off deletes rather than orphans — and
     * runs no nested hook, since the branch is being removed rather than written.
     * The paths a nested relationship hook saves are routed to that hook,
     * presence-gated exactly as the root edge gates its own, and leave the leaf
     * write; a load-only hook keeps its ordinary write.
     *
     * @throws LogicException when the property cannot be nulled, when the
     *                        association's target cannot be built without
     *                        arguments, or when a newly constructed related
     *                        record's assigned identifier is still incomplete
     *                        once it is written onto the record
     */
    public function saveStateToRelationship(object $record, mixed $state): void
    {
        $target = $this->requireSingularRelationshipTarget();
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

        $hooks = array_filter(
            $this->getMergedNestedRelationshipHooks(),
            static fn (Component $component): bool => $component->hasSaveRelationships(),
        );

        $claimedStatePaths = array_keys($hooks);

        foreach ($this->getRelatedStatePaths() as $statePath) {
            if ($this->isClaimedByNestedHook($statePath, $claimedStatePaths)) {
                continue;
            }

            if ($accessor->hasInArray($state, $statePath)) {
                $accessor->setOnRecord($related, $statePath, $accessor->getFromArray($state, $statePath));
            }
        }

        foreach ($hooks as $statePath => $component) {
            if ($accessor->hasInArray($state, $statePath)) {
                $component->saveRelationships($related, $accessor->getFromArray($state, $statePath));
            }
        }

        if ($existing !== null) {
            return;
        }

        $accessor->setOnRecord($record, $name, $related);

        if ($this->getAssociationReader()->hasIncompleteAssignedIdentifier($related)) {
            throw new LogicException(sprintf(
                'The [%s] relationship is saving a new [%s] whose identifier is incomplete, so no record key can be minted for it. Give every identifier field a component — enabled, dehydrated and required — inside the relationship, or on the record it hangs off when its identifier is that record, or drop relationship() and create the related record by hand after getData().',
                $name,
                $related::class,
            ));
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
     * The declared **leaf** paths this component's subtree carries, relative to
     * the related record — what a fill reads off it and a save writes back onto
     * it. A container nesting path-bearing components contributes its leaves
     * rather than itself, exactly as
     * {@see \Sylament\Schemas\Concerns\BelongsToRecord::getRecordPathMap()} filters
     * the root walk: its own path names a subtree, and writing that subtree as an
     * array is the write the mapper refuses.
     *
     * @return list<string>
     */
    private function getRelatedStatePaths(): array
    {
        return array_map(strval(...), array_keys($this->getRelatedStateComponents()));
    }

    /**
     * The components behind {@see getRelatedStatePaths()}, keyed by that path —
     * the last declared keeping a path two share, as the record path map's own
     * overwrite does — so a fill can ask the one reading a leaf about its casts.
     *
     * @return array<string, Component>
     */
    private function getRelatedStateComponents(): array
    {
        $prefix = $this->getStatePath() . '.';
        $components = [];

        foreach ($this->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getFlatComponents(withActions: false) as $component) {
                if (! $component->ownsRecordSubtree() && $component->hasPathBearingDescendants()) {
                    continue;
                }

                $statePath = $component->getStatePath();

                if (($statePath !== null) && str_starts_with($statePath, $prefix)) {
                    $components[mb_substr($statePath, mb_strlen($prefix))] = $component;
                }
            }
        }

        return $components;
    }

    /**
     * Every relationship hook this component's own walks route, merged across all
     * of its child-schema keys — decoration keys included, since
     * {@see getRelatedStatePaths()} walks them too — keyed relative to the
     * related record, the terms both leaf walks already speak. Merging in
     * {@see HasChildComponents::getChildSchemas()} order is what makes the last
     * assignment win the component the record path map keeps for a shared path:
     * the root's flat walk visits an owner's child schemas in that same order.
     *
     * @return array<string, Component>
     */
    private function getMergedNestedRelationshipHooks(): array
    {
        $hooks = [];

        foreach ($this->getChildSchemas() as $childSchema) {
            foreach ($childSchema->getNestedRelationshipHooks() as $statePath => $component) {
                $hooks[$statePath] = $component;
            }
        }

        return $hooks;
    }

    /**
     * Whether a nested hook already owns a leaf path — its own key, and
     * everything nested under it, because a hooked component owning its record
     * subtree still contributes its descendants' leaves to
     * {@see getRelatedStatePaths()}.
     *
     * @param list<string> $claimedStatePaths
     */
    private function isClaimedByNestedHook(string $statePath, array $claimedStatePaths): bool
    {
        return array_any($claimedStatePaths, fn (string $claimedStatePath): bool => ($statePath === $claimedStatePath) || str_starts_with($statePath, $claimedStatePath . '.'));
    }

    /**
     * The object the entangled association currently holds, null wherever the
     * record is unbound, unreadable or holds nothing yet.
     */
    private function getRelatedRecord(): ?object
    {
        $record = $this->getRecord();

        if (! is_object($record)) {
            return null;
        }

        try {
            $related = $this->getPropertyAccessor()->getFromRecord($record, $this->getRelationshipNameOrFail());
        } catch (AccessException) {
            return null;
        }

        return is_object($related) ? $related : null;
    }

    /**
     * The association's target class where the mapping answers, null where it
     * does not — {@see BelongsToRelationship::getRelationshipTarget()}'s refusals
     * kept off the structural reads the child-schema stamp is evaluated from.
     *
     * @return class-string|null
     */
    private function getSoftRelationshipTargetClass(): ?string
    {
        $name = $this->getRelationshipName();
        $recordClass = $this->getRecordClass();

        if (! filled($name) || ($recordClass === null)) {
            return null;
        }

        return $this->getAssociationReader()->describe($recordClass, $name)?->targetClass;
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
    private function requireSingularRelationshipTarget(): AssociationTarget
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

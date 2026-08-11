<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\State\AssociationTarget;

use function Sylament\Support\filled;

/**
 * The association a relation-backed component is bound to, and what the Doctrine
 * mapping declares at it. The `relationship()` setter itself stays on each
 * consumer — a field, a repeater and an entangled layout component give it
 * different signatures — while the name, the target resolution and its three-arm
 * refusal are shared. Three seams carry the prose that differs per consumer: how
 * a refusal names it, why a missing entity manager is fatal for it, and what to
 * do instead.
 */
trait BelongsToRelationship
{
    protected string|Closure|null $relationship = null;

    public function hasRelationship(): bool
    {
        return filled($this->getRelationshipName());
    }

    public function getRelationshipName(): ?string
    {
        return $this->evaluate($this->relationship);
    }

    /**
     * How the refusals name whatever declared the relationship, in their opening
     * position.
     */
    protected function getRelationshipSubject(): string
    {
        return static::class;
    }

    /**
     * What this component tells the user to do instead — the clause every arm of
     * {@see getRelationshipTarget()}'s refusal closes with.
     */
    abstract protected function getRelationshipFallbackAdvice(): string;

    /**
     * Why a missing entity manager is fatal for this component in particular, in
     * the "so …" position of the first refusal arm.
     */
    protected function getMissingEntityManagerConsequence(): string
    {
        return 'the association it maps could never be read';
    }

    /**
     * Where else the name could have come from, appended to
     * {@see getRelationshipNameOrFail()}'s refusal — empty where nothing but the
     * argument could ever have supplied it.
     */
    protected function getMissingRelationshipNameNote(): string
    {
        return '';
    }

    /**
     * @throws LogicException when a relationship is declared the mapping cannot
     *                        resolve — no entity manager, no record class, or no
     *                        association at that property
     */
    protected function getRelationshipTarget(): AssociationTarget
    {
        $name = $this->getRelationshipNameOrFail();
        $recordClass = $this->getRecordClass();
        $reader = $this->getAssociationReader();

        $target = ($recordClass === null) ? null : $reader->describe($recordClass, $name);

        if ($target instanceof AssociationTarget) {
            return $target;
        }

        throw new LogicException(sprintf(
            '%s declares relationship("%s"), but %s %s',
            $this->getRelationshipSubject(),
            $name,
            match (true) {
                ! $reader->hasEntityManager() => sprintf(
                    'no Doctrine EntityManager backs it, so %s. Register Doctrine (doctrine/doctrine-bundle),',
                    $this->getMissingEntityManagerConsequence(),
                ),
                $recordClass === null => 'the schema names no record class to read the association off. Give the schema a record() instance or class-string,',
                default => sprintf('[%s] maps no association at "%s". Name the property Doctrine maps,', $recordClass, $name),
            },
            $this->getRelationshipFallbackAdvice(),
        ));
    }

    /**
     * @throws LogicException when no relationship is declared at all
     */
    protected function getRelationshipNameOrFail(): string
    {
        $name = $this->getRelationshipName();

        if (filled($name)) {
            return $name;
        }

        throw new LogicException(sprintf(
            '%s was asked for its relationship, but relationship() named none%s.',
            $this->getRelationshipSubject(),
            $this->getMissingRelationshipNameNote(),
        ));
    }
}

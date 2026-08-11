<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use LogicException;
use Sylament\Schemas\State\AssociationTarget;

use function Sylament\Support\filled;

/**
 * The association a relation-backed field is bound to, and what the Doctrine
 * mapping declares at it. The `relationship()` setter itself stays on each
 * field — Filament gives them different signatures — while the name, the target
 * resolution and its three-arm refusal are shared.
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
     * What this field tells the user to do instead — the clause every arm of
     * {@see getRelationshipTarget()}'s refusal closes with.
     */
    abstract protected function getRelationshipFallbackAdvice(): string;

    /**
     * Why a missing entity manager is fatal for this field in particular, in the
     * "so …" position of the first refusal arm.
     */
    protected function getMissingEntityManagerConsequence(): string
    {
        return 'the association it maps could never be read';
    }

    /**
     * @throws LogicException when the field declares a relationship the mapping
     *                        cannot resolve — no entity manager, no record class,
     *                        or no association at that property
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
            'The [%s] field declares relationship("%s"), but %s %s',
            (string) $this->getName(),
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
     * @throws LogicException when the field declares no relationship at all
     */
    protected function getRelationshipNameOrFail(): string
    {
        $name = $this->getRelationshipName();

        if (filled($name)) {
            return $name;
        }

        throw new LogicException(sprintf(
            'The [%s] field was asked for its relationship, but relationship() named none and the field carries no name to fall back on.',
            (string) $this->getName(),
        ));
    }
}

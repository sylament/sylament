<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class RecordKey
{
    public const string SEPARATOR = '~';

    /**
     * @param class-string|null $class the mapped class to key the record by, when the caller declares one — a subclass the mapping does not know keys by it
     *
     * @throws LogicException when the identifier is incomplete, or a component is not printable
     */
    public static function of(EntityManagerInterface $entityManager, object $record, ?string $class = null): string
    {
        $class ??= $record::class;
        $values = self::flatten($entityManager, $record, $class)
            ?? throw new LogicException(sprintf('Object records must expose a readable non-blank identifier; [%s] has none set.', $record::class));

        return self::fromValues($entityManager, $class, $values);
    }

    /**
     * Null when the record carries no complete identifier yet — a component
     * that is null or still blank — which says nothing about whether it is
     * persisted: an assigned key is complete before the first flush.
     *
     * @param class-string|null $class the mapped class to key the record by, when the caller declares one
     *
     * @throws LogicException when a component is set but not printable
     */
    public static function find(EntityManagerInterface $entityManager, object $record, ?string $class = null): ?string
    {
        $class ??= $record::class;
        $values = self::flatten($entityManager, $record, $class);

        if ($values === null || array_any($values, static fn (mixed $value): bool => is_string($value) && mb_trim($value) === '')) {
            return null;
        }

        return self::fromValues($entityManager, $class, $values);
    }

    /**
     * Identifier values as a record holds them — already in their PHP form —
     * to the key a record of that class mints. Never a projected row's raw
     * columns: those go through {@see KeySelection::keyOf()}, which converts
     * each through its field's DBAL type first.
     *
     * @param class-string $class
     * @param list<mixed>  $values in the mapping's identifier order
     *
     * @throws LogicException when the values do not match the identifier, or one is not printable
     */
    public static function fromValues(EntityManagerInterface $entityManager, string $class, array $values): string
    {
        return IdentifierFields::of($entityManager, $class)->toKey($values);
    }

    /**
     * The identifier a key names, as `EntityManager::find()` takes it — each
     * field's value already the PHP value its DBAL type holds, an association
     * by its target's.
     *
     * @param class-string $class
     *
     * @return array<string, mixed>|null in the mapping's identifier order, null when the key is malformed for this class
     */
    public static function toIdentifier(EntityManagerInterface $entityManager, string $class, string $key): ?array
    {
        $fields = IdentifierFields::of($entityManager, $class);
        $values = $fields->toPhpValues($entityManager, $key);

        return $values === null ? null : array_combine($fields->names, $values);
    }

    /**
     * The record's identifier values as the mapping orders them, each already
     * the PHP value its own field's DBAL type holds and an identifier
     * association reduced to its target's — the values {@see of()} prints,
     * before they are printed. Null when a component is missing at any level.
     *
     * @internal read by the relation layer's owner bind, and not part of the key contract
     *
     * @param class-string|null $class the mapped class to read the record by, when the caller declares one
     *
     * @return list<mixed>|null
     */
    public static function values(EntityManagerInterface $entityManager, object $record, ?string $class = null): ?array
    {
        return self::flatten($entityManager, $record, $class ?? $record::class);
    }

    /**
     * The record's identifier values in the mapping's order, an association
     * reduced to its target's, or null when one is missing at any level:
     * Doctrine drops a null component rather than answering it.
     *
     * @param class-string $class
     *
     * @return list<mixed>|null
     */
    private static function flatten(EntityManagerInterface $entityManager, object $record, string $class): ?array
    {
        $metadata = $entityManager->getClassMetadata($class);
        $values = $metadata->getIdentifierValues($record);

        if (count($values) !== count($metadata->identifier)) {
            return null;
        }

        $flattened = [];

        foreach ($metadata->identifier as $field) {
            $value = $values[$field] ?? null;

            if ($metadata->hasAssociation($field)) {
                if (!is_object($value)) {
                    return null;
                }

                $target = self::flatten($entityManager, $value, $metadata->getAssociationTargetClass($field));

                if ($target === null) {
                    return null;
                }

                if (count($target) !== 1) {
                    throw new LogicException(sprintf('The identifier association [%s::$%s] targets a composite identifier, which cannot be a foreign identifier.', $metadata->getName(), $field));
                }

                $value = $target[0];
            }

            $flattened[] = $value;
        }

        return $flattened;
    }
}

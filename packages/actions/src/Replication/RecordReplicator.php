<?php

declare(strict_types=1);

namespace Sylament\Actions\Replication;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessor;
use LogicException;

final readonly class RecordReplicator
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * A new, unmanaged instance carrying the record's mapped fields, its to-one
     * owning-side references and a deep clone of each embeddable — never an
     * identifier, whether a field or an association, nor the version, a
     * generated column, an inverse side or a collection, which start empty.
     *
     * @param array<string> $excludedAttributes mapped field or association names left uncopied
     */
    public function replicate(object $record, array $excludedAttributes = []): object
    {
        $metadata = $this->entityManager->getClassMetadata($record::class);
        $replica = $metadata->newInstance();

        foreach ($this->getCopiedFieldNames($metadata, $excludedAttributes) as $field) {
            if ($this->isInitialized($metadata, $record, $field)) {
                $metadata->setFieldValue($replica, $field, $metadata->getFieldValue($record, $field));
            }
        }

        foreach (array_keys($metadata->embeddedClasses) as $property) {
            $accessor = $metadata->getPropertyAccessor($property);

            if (!$accessor instanceof PropertyAccessor || in_array($property, $excludedAttributes, true) || !$accessor->getUnderlyingReflector()->isInitialized($record)) {
                continue;
            }

            $value = $accessor->getValue($record);

            $accessor->setValue($replica, is_object($value) ? $this->cloneEmbeddable($value) : $value);
        }

        foreach ($metadata->associationMappings as $name => $mapping) {
            if (!$mapping->isToOne()) {
                $metadata->setFieldValue($replica, $name, new ArrayCollection());

                continue;
            }

            if ($mapping->isOwningSide() && !$metadata->isIdentifier($name) && !in_array($name, $excludedAttributes, true) && $this->isInitialized($metadata, $record, $name)) {
                $metadata->setFieldValue($replica, $name, $metadata->getFieldValue($record, $name));
            }
        }

        return $replica;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFieldValues(object $record): array
    {
        $metadata = $this->entityManager->getClassMetadata($record::class);
        $values = [];

        foreach ($this->getCopiedFieldNames($metadata, []) as $field) {
            if ($this->isInitialized($metadata, $record, $field)) {
                $values[$field] = $metadata->getFieldValue($record, $field);
            }
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $values
     */
    public function setFieldValues(object $record, array $values): void
    {
        $metadata = $this->entityManager->getClassMetadata($record::class);
        $fields = $this->getCopiedFieldNames($metadata, []);

        foreach ($values as $field => $value) {
            if (!in_array($field, $fields, true)) {
                throw new LogicException(sprintf('%s has no copyable mapped field [%s].', $record::class, $field));
            }

            $metadata->setFieldValue($record, $field, $value);
        }
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param array<string> $excludedAttributes
     *
     * @return list<string>
     */
    private function getCopiedFieldNames(ClassMetadata $metadata, array $excludedAttributes): array
    {
        $fields = [];

        foreach ($metadata->fieldMappings as $field => $mapping) {
            if (
                $metadata->isIdentifier($field)
                || ($metadata->isVersioned && $metadata->versionField === $field)
                || ($mapping->generated ?? ClassMetadata::GENERATED_NEVER) !== ClassMetadata::GENERATED_NEVER
                || $mapping->declaredField !== null
                || $mapping->notInsertable === true
                || in_array($field, $excludedAttributes, true)
            ) {
                continue;
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Doctrine maps a nested value object under a dotted key the owner has no
     * property accessor for, so each level is reached through its own
     * embeddable's metadata rather than through the record's.
     */
    private function cloneEmbeddable(object $value): object
    {
        $clone = clone $value;
        $metadata = $this->entityManager->getClassMetadata($value::class);

        foreach (array_keys($metadata->embeddedClasses) as $property) {
            $accessor = $metadata->getPropertyAccessor($property);

            if (!$accessor instanceof PropertyAccessor || !$accessor->getUnderlyingReflector()->isInitialized($clone)) {
                continue;
            }

            $nested = $accessor->getValue($clone);

            if (is_object($nested)) {
                $accessor->setValue($clone, $this->cloneEmbeddable($nested));
            }
        }

        return $clone;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function isInitialized(ClassMetadata $metadata, object $record, string $property): bool
    {
        $accessor = $metadata->getPropertyAccessor($property)
            ?? throw new LogicException(sprintf('%s maps no property [%s].', $metadata->getName(), $property));

        return $accessor->getUnderlyingReflector()->isInitialized($record);
    }
}

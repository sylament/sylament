<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use BackedEnum;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Stringable;

/**
 * @internal
 */
final readonly class IdentifierFields
{
    /**
     * @param class-string $class
     * @param list<string> $names        in the mapping's identifier order
     * @param list<bool>   $associations whether the field at that index is a to-one association
     * @param list<string> $types        the DBAL type name a value of that field binds as
     */
    private function __construct(
        public string $class,
        public array $names,
        public array $associations,
        public array $types,
    ) {
    }

    /**
     * @param class-string $class
     */
    public static function of(EntityManagerInterface $entityManager, string $class): self
    {
        $metadata = $entityManager->getClassMetadata($class);
        $associations = [];
        $types = [];

        foreach ($metadata->identifier as $name) {
            $associations[] = $metadata->hasAssociation($name);

            $owner = $metadata;
            $field = $name;

            while ($owner->hasAssociation($field)) {
                $owner = $entityManager->getClassMetadata($owner->getAssociationTargetClass($field));
                $field = $owner->getSingleIdentifierFieldName();
            }

            $types[] = $owner->getTypeOfField($field)
                ?? throw new LogicException(sprintf('The identifier field [%s::$%s] maps no DBAL type, so a record key cannot be bound against it.', $class, $name));
        }

        return new self($metadata->getName(), $metadata->identifier, $associations, $types);
    }

    /**
     * The one string form an identifier value has on either side of a key, or
     * null when it has none.
     */
    public static function printable(mixed $value): ?string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (!is_scalar($value)) {
            return null;
        }

        $printed = (string) $value;

        return mb_check_encoding($printed, 'UTF-8') && mb_trim($printed) !== '' ? $printed : null;
    }

    public function isComposite(): bool
    {
        return count($this->names) > 1;
    }

    /**
     * @param list<string> $components
     */
    public function join(array $components): string
    {
        if (!$this->isComposite()) {
            return $components[0];
        }

        return implode(RecordKey::SEPARATOR, array_map($this->escape(...), $components));
    }

    /**
     * The key's components as the PHP values their fields hold, or null when
     * the string is not the one a record of this class mints: the wrong arity,
     * a non-canonical escape, a value its field's type refuses, or a spelling
     * that type would print back differently.
     *
     * @return list<mixed>|null
     */
    public function toPhpValues(EntityManagerInterface $entityManager, string $key): ?array
    {
        $components = $this->split($key);

        if ($components === null) {
            return null;
        }

        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $values = [];

        foreach ($components as $index => $component) {
            try {
                $value = Type::getType($this->types[$index])->convertToPHPValue($component, $platform);
            } catch (ConversionException) {
                // A key is client input: a component its field's type refuses names no record, as an unknown key does.
                return null;
            }

            if (self::printable($value) !== $component) {
                return null;
            }

            $values[] = $value;
        }

        return $values;
    }

    /**
     * The same values as the database holds them, for binding.
     *
     * @return list<mixed>|null
     */
    public function toDatabaseValues(EntityManagerInterface $entityManager, string $key): ?array
    {
        $values = $this->toPhpValues($entityManager, $key);

        if ($values === null) {
            return null;
        }

        $platform = $entityManager->getConnection()->getDatabasePlatform();

        return array_map(
            static fn (mixed $value, string $type): mixed => Type::getType($type)->convertToDatabaseValue($value, $platform),
            $values,
            $this->types,
        );
    }

    /**
     * @return list<string>|null
     */
    public function split(string $key): ?array
    {
        if (mb_trim($key) === '') {
            return null;
        }

        $components = $this->isComposite() ? $this->unescape(explode(RecordKey::SEPARATOR, $key)) : [$key];

        if ($components === null) {
            return null;
        }

        foreach ($components as $index => $component) {
            if (!$this->hasShapeOf($component, $this->types[$index])) {
                return null;
            }
        }

        return $components;
    }

    /**
     * @param list<string> $parts
     *
     * @return list<string>|null
     */
    private function unescape(array $parts): ?array
    {
        if (count($parts) !== count($this->names)) {
            return null;
        }

        $components = [];

        foreach ($parts as $part) {
            if (preg_match('/^(?:[A-Za-z0-9_-]++|=[0-9A-F]{2})++$/D', $part) !== 1) {
                return null;
            }

            $component = preg_replace_callback('/=([0-9A-F]{2})/', static fn (array $match): string => pack('H2', $match[1]), $part);

            if ($component === null || !mb_check_encoding($component, 'UTF-8') || $this->escape($component) !== $part) {
                return null;
            }

            $components[] = $component;
        }

        return $components;
    }

    private function escape(string $component): string
    {
        return preg_replace_callback('/[^A-Za-z0-9_-]/', static fn (array $match): string => sprintf('=%02X', ord($match[0])), $component)
            ?? throw new LogicException('A record key component could not be escaped.');
    }

    /**
     * The two types whose own conversion hands a malformed string back
     * untouched, so nothing downstream would refuse it before the database does.
     */
    private function hasShapeOf(string $component, string $type): bool
    {
        return match ($type) {
            Types::BIGINT => preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $component) === 1,
            Types::GUID => preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $component) === 1,
            default => true,
        };
    }
}

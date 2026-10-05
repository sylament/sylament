<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use BackedEnum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\Type;
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
     * null when it has none. A NUL byte has none: a text parameter reaches
     * PostgreSQL as a C string, so a component carrying one would be compared
     * by its prefix and give a record a second spelling.
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

        return mb_check_encoding($printed, 'UTF-8') && !str_contains($printed, "\0") && mb_trim($printed) !== '' ? $printed : null;
    }

    public function isComposite(): bool
    {
        return count($this->names) > 1;
    }

    /**
     * The key a record holding these identifier values mints — the one print
     * step both the record side and the row side go through.
     *
     * @param list<mixed> $values in the mapping's identifier order
     *
     * @throws LogicException when the values do not match the identifier, or one is not printable
     */
    public function toKey(array $values): string
    {
        if (count($values) !== count($this->names)) {
            throw new LogicException(sprintf('A key of [%s] takes %d identifier values, %d given.', $this->class, count($this->names), count($values)));
        }

        return $this->join(array_map(
            fn (mixed $value): string => self::printable($value) ?? throw $this->refuseUnprintable($values),
            $values,
        ));
    }

    /**
     * @param list<mixed> $values
     */
    private function refuseUnprintable(array $values): LogicException
    {
        $held = [];

        foreach ($this->names as $index => $name) {
            $held[] = sprintf('%s %s', $name, $this->describe($values[$index] ?? null));
        }

        return new LogicException(sprintf('The identifier of [%s] is not a printable value. It holds %s.', $this->class, implode(', ', $held)));
    }

    private function describe(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (is_string($value)) {
            return json_encode(mb_strcut($value, 0, 64), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        }

        return is_scalar($value) || $value === null ? var_export($value, true) : get_debug_type($value);
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
        $platform = $entityManager->getConnection()->getDatabasePlatform();
        $components = $this->split($key, $platform);

        if ($components === null) {
            return null;
        }

        $values = [];

        foreach ($components as $index => $component) {
            $type = Type::getType($this->types[$index]);

            try {
                $value = $type->convertToPHPValue($component, $platform);
            } catch (ConversionException) {
                $value = $this->readThroughDatabaseForm($type, $component, $platform);
            }

            if (self::printable($value) !== $component) {
                $value = $this->readThroughDatabaseForm($type, $component, $platform);

                if (self::printable($value) !== $component) {
                    return null;
                }
            }

            $values[] = $value;
        }

        return $values;
    }

    /**
     * A component the field's type refuses to read, or reads as another value,
     * offered to its writer first: a type whose column holds a form its printed
     * one is not — a binary-backed uuid, an integer printed as hex — takes that
     * printed spelling on the way in and reads the database form it makes back.
     * Null when either step refuses, which the print-back rule then answers as a
     * key naming no record.
     */
    private function readThroughDatabaseForm(Type $type, string $component, AbstractPlatform $platform): mixed
    {
        try {
            return $type->convertToPHPValue($type->convertToDatabaseValue($component, $platform), $platform);
        } catch (ConversionException) {
            // A key is client input: a component its field's type refuses both ways names no record, as an unknown key does.
            return null;
        }
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
    public function split(string $key, AbstractPlatform $platform): ?array
    {
        if (mb_trim($key) === '') {
            return null;
        }

        $components = $this->isComposite() ? $this->unescape(explode(RecordKey::SEPARATOR, $key)) : [$key];

        if ($components === null) {
            return null;
        }

        foreach ($components as $index => $component) {
            if (!$this->hasShapeOf($component, $this->types[$index], $platform)) {
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
     * The three type families whose own conversion hands a malformed string
     * back untouched, so nothing downstream would refuse it before the database
     * does, and the boolean one, which prints only `0` and `1` and whose writer
     * refuses another spelling on PostgreSQL with no ConversionException — each
     * matched by class, so a type registered under a name of its own is checked
     * as the family it extends.
     */
    private function hasShapeOf(string $component, string $type, AbstractPlatform $platform): bool
    {
        $instance = Type::getType($type);

        if ($instance instanceof BooleanType) {
            return $component === '0' || $component === '1';
        }

        if ($instance instanceof BigIntType) {
            return preg_match('/^(?:0|-?[1-9][0-9]*)$/D', $component) === 1;
        }

        if ($instance instanceof DecimalType) {
            return preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/D', $component) === 1;
        }

        if ($instance instanceof GuidType) {
            return !$this->hasNativeGuidType($platform)
                || preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D', $component) === 1;
        }

        return true;
    }

    /**
     * Whether the platform declares a guid type of its own rather than falling
     * back to a fixed 36-character string. Only there is a component that is not
     * a UUID a syntax error; on a CHAR(36) the column holds whatever a record
     * put in it, and refusing that spelling would refuse a key the record side
     * minted.
     */
    private function hasNativeGuidType(AbstractPlatform $platform): bool
    {
        return $platform->getGuidTypeDeclarationSQL([]) !== $platform->getStringTypeDeclarationSQL(['fixed' => true, 'length' => 36]);
    }
}

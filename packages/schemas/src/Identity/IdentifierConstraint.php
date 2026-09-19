<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use LogicException;

final class IdentifierConstraint
{
    private const int MAX_PARAMETERS_PER_STATEMENT = 2000;

    private const int MAX_COMPOSITE_KEYS_PER_STATEMENT = 500;

    /**
     * Null when the key is malformed for the class, so it names no record.
     *
     * @param class-string $class
     */
    public static function equals(QueryBuilder $query, string $alias, string $class, string $key, string $parameter): ?string
    {
        self::assertParameterBase($parameter);

        $fields = IdentifierFields::of($query->getEntityManager(), $class);
        $values = $fields->toDatabaseValues($query->getEntityManager(), $key);

        return $values === null ? null : self::bind($query, $alias, $fields, $values, $parameter);
    }

    /**
     * Null when the key is malformed for the class, so there is nothing to exclude.
     *
     * @param class-string $class
     */
    public static function notEquals(QueryBuilder $query, string $alias, string $class, string $key, string $parameter): ?string
    {
        self::assertParameterBase($parameter);

        $fields = IdentifierFields::of($query->getEntityManager(), $class);
        $values = $fields->toDatabaseValues($query->getEntityManager(), $key);

        if ($values === null) {
            return null;
        }

        if (!$fields->isComposite()) {
            $query->setParameter($parameter, $values[0], Type::getType($fields->types[0])->getBindingType());

            return sprintf('%s.%s <> :%s', $alias, $fields->names[0], $parameter);
        }

        return sprintf('NOT (%s)', self::bind($query, $alias, $fields, $values, $parameter));
    }

    /**
     * @param class-string $class
     * @param list<string> $keys
     */
    public static function in(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): string
    {
        return self::membership($query, $alias, $class, $keys, $parameter) ?? '1 = 0';
    }

    /**
     * Null when no well-formed key remains, so there is nothing to exclude.
     *
     * @param class-string $class
     * @param list<string> $keys
     */
    public static function notIn(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): ?string
    {
        $fields = IdentifierFields::of($query->getEntityManager(), $class);
        $membership = self::membership($query, $alias, $class, $keys, $parameter);

        if ($membership === null) {
            return null;
        }

        return $fields->isComposite()
            ? sprintf('NOT (%s)', $membership)
            : sprintf('%s.%s NOT IN (:%s)', $alias, $fields->names[0], $parameter);
    }

    /**
     * @param class-string $class
     */
    public static function correlate(QueryBuilder $query, string $alias, string $class, string $outerAlias): string
    {
        return implode(' AND ', array_map(
            static fn (string $field): string => sprintf('%s.%s = %s.%s', $alias, $field, $outerAlias, $field),
            IdentifierFields::of($query->getEntityManager(), $class)->names,
        ));
    }

    /**
     * @param class-string $class
     */
    public static function select(QueryBuilder $query, string $alias, string $class, string $resultAlias = 'sy_key'): KeySelection
    {
        $fields = IdentifierFields::of($query->getEntityManager(), $class);
        $expressions = [];
        $resultAliases = [];

        foreach ($fields->names as $index => $field) {
            $resultAliases[] = "{$resultAlias}_{$index}";
            $expressions[] = sprintf(
                $fields->associations[$index] ? 'IDENTITY(%s.%s) AS %s' : '%s.%s AS %s',
                $alias,
                $field,
                "{$resultAlias}_{$index}",
            );
        }

        return new KeySelection($query->getEntityManager(), $fields, $expressions, $resultAliases);
    }

    /**
     * @param class-string $class
     *
     * @return list<string>
     */
    public static function groupBy(QueryBuilder $query, string $alias, string $class): array
    {
        return array_map(
            static fn (string $field): string => "{$alias}.{$field}",
            IdentifierFields::of($query->getEntityManager(), $class)->names,
        );
    }

    /**
     * @param class-string $class
     */
    public static function orderBy(QueryBuilder $query, string $alias, string $class, string $direction): void
    {
        $ordered = [];

        /** @var list<OrderBy> $orderings */
        $orderings = $query->getDQLPart('orderBy');

        foreach ($orderings as $ordering) {
            foreach ($ordering->getParts() as $part) {
                $ordered[] = explode(' ', $part, 2)[0];
            }
        }

        foreach (self::groupBy($query, $alias, $class) as $path) {
            if (!in_array($path, $ordered, true)) {
                $query->addOrderBy($path, $direction);
            }
        }
    }

    /**
     * @param class-string $class
     * @param list<string> $keys
     *
     * @return list<list<string>>
     */
    public static function chunk(EntityManagerInterface $entityManager, string $class, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $width = count(IdentifierFields::of($entityManager, $class)->names);

        return array_chunk(array_values(array_unique($keys)), $width === 1
            ? self::MAX_PARAMETERS_PER_STATEMENT
            : max(1, min(self::MAX_COMPOSITE_KEYS_PER_STATEMENT, intdiv(self::MAX_PARAMETERS_PER_STATEMENT, $width))));
    }

    /**
     * @param class-string $class
     * @param list<string> $keys
     */
    private static function membership(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): ?string
    {
        self::assertParameterBase($parameter);

        $fields = IdentifierFields::of($query->getEntityManager(), $class);
        $tuples = [];

        foreach (array_values(array_unique($keys)) as $key) {
            $values = $fields->toDatabaseValues($query->getEntityManager(), $key);

            if ($values !== null) {
                $tuples[] = $values;
            }
        }

        if ($tuples === []) {
            return null;
        }

        if (!$fields->isComposite()) {
            $query->setParameter($parameter, array_column($tuples, 0), match (Type::getType($fields->types[0])->getBindingType()) {
                ParameterType::INTEGER => ArrayParameterType::INTEGER,
                ParameterType::ASCII => ArrayParameterType::ASCII,
                ParameterType::BINARY => ArrayParameterType::BINARY,
                default => ArrayParameterType::STRING,
            });

            return sprintf('%s.%s IN (:%s)', $alias, $fields->names[0], $parameter);
        }

        $terms = [];

        foreach ($tuples as $index => $values) {
            $terms[] = sprintf('(%s)', self::bind($query, $alias, $fields, $values, "{$parameter}_{$index}"));
        }

        return self::disjunction($terms, 0, count($terms));
    }

    /**
     * A balanced tree rather than a flat chain: SQLite refuses an expression
     * nested deeper than a thousand levels, which a flat OR of that many
     * terms is.
     *
     * @param list<string> $terms
     */
    private static function disjunction(array $terms, int $offset, int $length): string
    {
        if ($length === 1) {
            return $terms[$offset];
        }

        $half = intdiv($length, 2);

        return sprintf('(%s OR %s)', self::disjunction($terms, $offset, $half), self::disjunction($terms, $offset + $half, $length - $half));
    }

    /**
     * @param list<mixed> $values
     */
    private static function bind(QueryBuilder $query, string $alias, IdentifierFields $fields, array $values, string $parameter): string
    {
        $comparisons = [];

        foreach ($fields->names as $index => $field) {
            $name = $fields->isComposite() ? "{$parameter}_{$index}" : $parameter;

            $query->setParameter($name, $values[$index], Type::getType($fields->types[$index])->getBindingType());

            $comparisons[] = sprintf('%s.%s = :%s', $alias, $field, $name);
        }

        return implode(' AND ', $comparisons);
    }

    /**
     * A composite key derives `<base>_<j>` and `<base>_<i>_<j>` from the base,
     * so a base that already ends that way could mint another base's name —
     * and `setParameter()` overwrites in silence.
     *
     * @throws LogicException when the base ends in an underscore and digits
     */
    private static function assertParameterBase(string $parameter): void
    {
        if (preg_match('/_[0-9]+$/D', $parameter) === 1) {
            throw new LogicException(sprintf('The parameter base [%s] ends in an index of its own; name it without one, since a composite identifier appends "_<key>_<field>" to it.', $parameter));
        }
    }
}

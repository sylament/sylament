<?php

declare(strict_types=1);

namespace Sylament\Schemas\Identity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use LogicException;
use Sylament\Schemas\Query\SortDirections;

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

        return $values === null ? null : self::bind($query, self::paths($alias, $fields), $fields, $values, $parameter);
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

        return sprintf('NOT (%s)', self::bind($query, self::paths($alias, $fields), $fields, $values, $parameter));
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
     * {@see in()} over raw SQL, for a statement that names one table and so has
     * no identification variable to qualify a field with: the class's own
     * identifier columns, quoted where the mapping quotes them, bound on a DBAL
     * builder — which carries no entity manager, so it is handed in.
     *
     * @param class-string $class
     * @param list<string> $keys
     */
    public static function inColumns(EntityManagerInterface $entityManager, DbalQueryBuilder $query, string $class, array $keys, string $parameter): string
    {
        self::assertParameterBase($parameter);

        $fields = IdentifierFields::of($entityManager, $class);
        $columns = $entityManager->getConfiguration()->getQuoteStrategy()->getIdentifierColumnNames(
            $entityManager->getClassMetadata($class),
            $entityManager->getConnection()->getDatabasePlatform(),
        );

        return self::memberOf($query, $columns, $fields, self::tuples($entityManager, $fields, $keys), $parameter) ?? '1 = 0';
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
        return self::paths($alias, IdentifierFields::of($query->getEntityManager(), $class));
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
                $query->addOrderBy($path, SortDirections::from($direction));
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
     * A client-sized key list as the batches a membership over it runs in: the
     * whole list at once for a single identifier, and {@see chunk()}'s batches
     * for a composite one.
     *
     * @param class-string $class
     * @param list<string> $keys
     *
     * @return list<list<string>>
     */
    public static function batch(EntityManagerInterface $entityManager, string $class, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        return IdentifierFields::of($entityManager, $class)->isComposite()
            ? self::chunk($entityManager, $class, $keys)
            : [$keys];
    }

    /**
     * @param class-string $class
     * @param list<string> $keys
     */
    private static function membership(QueryBuilder $query, string $alias, string $class, array $keys, string $parameter): ?string
    {
        self::assertParameterBase($parameter);

        $fields = IdentifierFields::of($query->getEntityManager(), $class);

        return self::memberOf($query, self::paths($alias, $fields), $fields, self::tuples($query->getEntityManager(), $fields, $keys), $parameter);
    }

    /**
     * The one membership body both vocabularies share: each tuple compared field
     * by field against its operand — `alias.field` in DQL, the column in SQL —
     * a single identifier as one `IN` list, a composite one as a balanced OR of
     * ANDs. Null when no tuple is left.
     *
     * @param list<string>      $operands in the mapping's identifier order
     * @param list<list<mixed>> $tuples
     */
    private static function memberOf(QueryBuilder|DbalQueryBuilder $query, array $operands, IdentifierFields $fields, array $tuples, string $parameter): ?string
    {
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

            return sprintf('%s IN (:%s)', $operands[0], $parameter);
        }

        if ($query instanceof QueryBuilder && $query->getParameter("{$parameter}_0_0") instanceof Parameter) {
            throw new LogicException(sprintf('The parameter base [%s] is already bound on this query builder, and a second membership over it would bind its keys twice; give each membership a base of its own.', $parameter));
        }

        $terms = [];

        foreach ($tuples as $index => $values) {
            $terms[] = sprintf('(%s)', self::bind($query, $operands, $fields, $values, "{$parameter}_{$index}", append: true));
        }

        return self::disjunction($terms, 0, count($terms));
    }

    /**
     * The well-formed keys as the values the database holds, each once; a key no
     * record of the class could mint is dropped rather than bound.
     *
     * @param list<string> $keys
     *
     * @return list<list<mixed>>
     */
    private static function tuples(EntityManagerInterface $entityManager, IdentifierFields $fields, array $keys): array
    {
        $tuples = [];

        foreach (array_values(array_unique($keys)) as $key) {
            $values = $fields->toDatabaseValues($entityManager, $key);

            if ($values !== null) {
                $tuples[] = $values;
            }
        }

        return $tuples;
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
     * @param list<string> $operands in the mapping's identifier order
     * @param list<mixed>  $values
     */
    private static function bind(QueryBuilder|DbalQueryBuilder $query, array $operands, IdentifierFields $fields, array $values, string $parameter, bool $append = false): string
    {
        $comparisons = [];

        foreach ($operands as $index => $operand) {
            $name = $fields->isComposite() ? "{$parameter}_{$index}" : $parameter;
            $type = Type::getType($fields->types[$index])->getBindingType();

            if ($append && $query instanceof QueryBuilder) {
                $query->getParameters()->add(new Parameter($name, $values[$index], $type));
            } else {
                $query->setParameter($name, $values[$index], $type);
            }

            $comparisons[] = sprintf('%s = :%s', $operand, $name);
        }

        return implode(' AND ', $comparisons);
    }

    /**
     * @return list<string>
     */
    private static function paths(string $alias, IdentifierFields $fields): array
    {
        return array_map(static fn (string $field): string => "{$alias}.{$field}", $fields->names);
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

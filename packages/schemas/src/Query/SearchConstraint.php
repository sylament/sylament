<?php

declare(strict_types=1);

namespace Sylament\Schemas\Query;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;

final class SearchConstraint
{
    /**
     * @param class-string|null $class the entity the path's alias stands for, when the builder does not declare that alias — a subselect's
     */
    public static function like(QueryBuilder $query, string $path, string $search, ?bool $forceCaseInsensitive, ?string $class = null): Comparison
    {
        $isPostgreSql = $query->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $caseInsensitive = $forceCaseInsensitive ?? $isPostgreSql;
        $operand = $isPostgreSql && self::holdsNoCharacterData($query, $path, $class) ? "CONCAT({$path}, '')" : $path;

        $parameter = 'sylament_search_' . count($query->getParameters());

        if ($caseInsensitive) {
            $query->setParameter($parameter, '%' . mb_strtolower($search) . '%');

            return $query->expr()->like("LOWER({$operand})", ":{$parameter}");
        }

        $query->setParameter($parameter, '%' . $search . '%');

        return $query->expr()->like($operand, ":{$parameter}");
    }

    /**
     * Whether the path reads a mapped field whose type is neither a `TextType`
     * nor a `StringType` other than a `GuidType` — a value PostgreSQL compares
     * with `LIKE` only once it is text. False for a path it cannot resolve.
     *
     * @param class-string|null $class
     */
    private static function holdsNoCharacterData(QueryBuilder $query, string $path, ?string $class): bool
    {
        if (!str_contains($path, '.')) {
            return false;
        }

        [$alias, $field] = explode('.', $path, 2);
        $class ??= QueryAliases::classOf($query, $alias);

        if ($class === null) {
            return false;
        }

        $metadata = $query->getEntityManager()->getClassMetadata($class);
        $type = $metadata->hasField($field) ? $metadata->getTypeOfField($field) : null;

        if ($type === null) {
            return false;
        }

        $instance = Type::getType($type);

        return !$instance instanceof TextType && (!$instance instanceof StringType || $instance instanceof GuidType);
    }
}

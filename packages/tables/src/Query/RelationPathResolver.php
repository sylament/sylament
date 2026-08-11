<?php

declare(strict_types=1);

namespace Sylament\Tables\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use LogicException;

final class RelationPathResolver
{
    /**
     * Null when the path names nothing to cross: it carries no dot, its head is
     * an alias the builder already declares — so the caller's own resolution
     * wins — the query declares no root entity, or the mapping resolves none of
     * it.
     */
    public static function analyseQueryPath(QueryBuilder $query, string $path): ?ResolvedRelationPath
    {
        if (!str_contains($path, '.')) {
            return null;
        }

        if (in_array(explode('.', $path, 2)[0], $query->getAllAliases(), true)) {
            return null;
        }

        $rootClass = $query->getRootEntities()[0] ?? null;

        if ($rootClass === null) {
            return null;
        }

        return self::analyse($query->getEntityManager(), $rootClass, $path);
    }

    /**
     * Null when a step's remainder names no field and its head names no
     * association, so the mapping does not resolve the path at all.
     *
     * @param class-string $rootClass
     *
     * @throws LogicException when the path ends in an association rather than a field
     */
    public static function analyse(EntityManagerInterface $entityManager, string $rootClass, string $path): ?ResolvedRelationPath
    {
        $segments = explode('.', $path);
        $hops = [];
        $currentClass = $rootClass;

        while ($segments !== []) {
            $metadata = $entityManager->getClassMetadata($currentClass);
            $remainder = implode('.', $segments);

            if ($metadata->hasField($remainder)) {
                return new ResolvedRelationPath($hops, $remainder);
            }

            $head = $segments[0];

            if (!$metadata->hasAssociation($head)) {
                return null;
            }

            $currentClass = $metadata->getAssociationTargetClass($head);
            $hops[] = new Hop($head, $currentClass, $metadata->isCollectionValuedAssociation($head));
            array_shift($segments);
        }

        throw new LogicException(sprintf('Cannot resolve [%s]: the path ends in an association rather than a field — name a field on it, e.g. [%s.<field>].', $path, $path));
    }
}

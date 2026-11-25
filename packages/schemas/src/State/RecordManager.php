<?php

declare(strict_types=1);

namespace Sylament\Schemas\State;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use LogicException;

final class RecordManager
{
    /**
     * @param class-string $class
     *
     * @throws LogicException when no manager maps the class, or the one that does is not an ORM entity manager
     */
    public static function of(ManagerRegistry $registry, string $class): EntityManagerInterface
    {
        return self::find($registry, $class)
            ?? throw new LogicException('No Doctrine manager handles ' . $class . '.');
    }

    /**
     * Null when no manager maps the class, an anonymous class included.
     *
     * @param class-string $class
     *
     * @throws LogicException when the manager that maps the class is not an ORM entity manager
     */
    public static function find(ManagerRegistry $registry, string $class): ?EntityManagerInterface
    {
        $manager = $registry->getManagerForClass($class);

        if (!$manager instanceof ObjectManager) {
            return null;
        }

        if (! $manager instanceof EntityManagerInterface) {
            throw new LogicException(sprintf('Doctrine manages %s through a %s, not an ORM entity manager.', $class, get_debug_type($manager)));
        }

        return $manager;
    }
}

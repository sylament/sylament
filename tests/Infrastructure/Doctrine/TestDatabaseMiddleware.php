<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Doctrine;

use Closure;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\Persistence\ManagerRegistry;
use SensitiveParameter;

/**
 * Prepares the test's database before the kernel's connection connects, and on
 * SQLite drops the file's durability once connected. DBAL connects lazily, so
 * building a manager prepares nothing.
 */
final readonly class TestDatabaseMiddleware implements Middleware
{
    /**
     * @param Closure(): ManagerRegistry $doctrine a closure, so the registry is built only when a connection opens
     */
    public function __construct(
        private Closure $doctrine,
    ) {
    }

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new class ($driver, $this->doctrine) extends AbstractDriverMiddleware {
            /**
             * @param Closure(): ManagerRegistry $doctrine
             */
            public function __construct(
                DriverInterface $driver,
                private readonly Closure $doctrine,
            ) {
                parent::__construct($driver);
            }

            /**
             * {@inheritDoc}
             */
            public function connect(
                #[SensitiveParameter]
                array $params,
            ): ConnectionInterface {
                TestDatabase::prepare($this->doctrine);
                $connection = parent::connect($params);

                if (TestPlatform::current() === TestPlatform::Sqlite) {
                    // The file lives for one test, so its durability buys nothing.
                    $connection->exec('PRAGMA synchronous = OFF');
                    $connection->exec('PRAGMA journal_mode = MEMORY');
                }

                return $connection;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Infrastructure\Doctrine;

use Closure;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Records the SQL of every statement the connection runs, so a test can assert
 * what a read costs in queries. A per-package suite passes it to its
 * `EntityManagerFactory::create()`; the root suite reads the one DoctrineBundle
 * built for the connection, as `InteractsWithDatabase` does. {@see reset()} it
 * after seeding, so only the statements under test count.
 */
final class RecordingMiddleware implements Middleware
{
    /**
     * @var list<string>
     */
    private array $statements = [];

    private bool $suspended = false;

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new RecordingDriver($driver, $this);
    }

    public function record(string $sql): void
    {
        if (!$this->suspended) {
            $this->statements[] = $sql;
        }
    }

    /**
     * @return list<string>
     */
    public function statements(): array
    {
        return $this->statements;
    }

    /**
     * The recorded statements with Doctrine's minted names renumbered, for a pin
     * that has to survive an ORM release.
     *
     * @return list<string>
     */
    public function normalizedStatements(): array
    {
        return array_map(SqlNormalizer::normalize(...), $this->statements);
    }

    public function reset(): void
    {
        $this->statements = [];
    }

    /**
     * Runs the callback with recording suspended and returns what it returns:
     * how a test database's schema build stays out of the statement list.
     */
    public function withoutRecording(Closure $callback): mixed
    {
        $this->suspended = true;

        try {
            return $callback();
        } finally {
            $this->suspended = false;
        }
    }
}

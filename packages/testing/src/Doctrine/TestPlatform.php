<?php

declare(strict_types=1);

namespace Sylament\Testing\Doctrine;

use LogicException;

/**
 * The database the test suites run on, read from SYLAMENT_TEST_DATABASE_URL:
 * unset or empty means in-memory SQLite.
 */
enum TestPlatform
{
    case Sqlite;
    case MySql;
    case PostgreSql;

    public const string URL_VARIABLE = 'SYLAMENT_TEST_DATABASE_URL';

    public static function current(): self
    {
        $url = self::url();

        if ($url === null) {
            return self::Sqlite;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return match (is_string($scheme) ? mb_strtolower($scheme) : '') {
            'mysql' => self::MySql,
            'postgresql', 'pgsql' => self::PostgreSql,
            default => throw new LogicException(sprintf('%s must name a mysql:// or postgresql:// server; its scheme is [%s].', self::URL_VARIABLE, is_string($scheme) ? $scheme : '')),
        };
    }

    public static function url(): ?string
    {
        $url = getenv(self::URL_VARIABLE);

        return is_string($url) && $url !== '' ? $url : null;
    }
}

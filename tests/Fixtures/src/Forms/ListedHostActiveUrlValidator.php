<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Validation\ActiveUrlValidator;

/**
 * An `ActiveUrl` validator whose resolver answers from a list instead of the network: only
 * the hosts in {@see RESOLVING_HOSTS} have a record.
 */
final class ListedHostActiveUrlValidator extends ActiveUrlValidator
{
    public const array RESOLVING_HOSTS = ['resolves.test'];

    protected function hasDnsRecord(string $host): bool
    {
        return in_array($host, self::RESOLVING_HOSTS, true);
    }
}

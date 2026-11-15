<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Support;

use DateTimeInterface;
use League\Flysystem\Config;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

/**
 * Mints a temporary URL carrying its expiry, as a storage that signs its links does.
 */
final class SignedUrlGenerator implements TemporaryUrlGenerator
{
    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, Config $config): string
    {
        return "https://files.example.com/{$path}?expires={$expiresAt->getTimestamp()}";
    }
}

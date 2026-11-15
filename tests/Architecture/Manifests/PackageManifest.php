<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Manifests;

use JsonException;
use UnexpectedValueException;

/**
 * Reads a package's composer.json, or its asset workspace's package.json, for the scans of what it declares.
 */
final class PackageManifest
{
    /**
     * @return array<mixed>
     *
     * @throws JsonException
     */
    public static function of(string $package): array
    {
        return self::read(dirname(__DIR__, 3) . "/packages/{$package}/composer.json");
    }

    /**
     * @return array<mixed>
     *
     * @throws JsonException
     */
    public static function assets(string $package): array
    {
        return self::read(dirname(__DIR__, 3) . "/packages/{$package}/assets/package.json");
    }

    /**
     * @return array<mixed>
     *
     * @throws JsonException
     */
    private static function read(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new UnexpectedValueException("Unable to read {$path}.");
        }

        $manifest = json_decode($contents, associative: true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($manifest)) {
            throw new UnexpectedValueException("{$path} is not a JSON object.");
        }

        return $manifest;
    }
}

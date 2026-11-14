<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Manifests;

use JsonException;
use UnexpectedValueException;

/**
 * Reads a package's composer.json for the scans of what it declares.
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
        $path = dirname(__DIR__, 3) . "/packages/{$package}/composer.json";
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

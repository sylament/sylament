<?php

declare(strict_types=1);

/*
 * Usage: composer docs:index
 *
 * Writes the generated table between the trail-index markers of docs/progress/README.md and of each
 * docs/progress/<package>/README.md, and prints each README path it changed. Exits 0 on success, and 1 with
 * the reason on STDERR when a README lacks its markers or a trail file cannot be parsed; it then writes nothing.
 */

use Sylament\Schemas\Tests\Architecture\Trail\Trail;

$root = dirname(__DIR__, 5);

require $root . '/vendor/autoload.php';

try {
    $changed = new Trail($root)->writeIndexes();
} catch (UnexpectedValueException $unexpectedValueException) {
    fwrite(STDERR, $unexpectedValueException->getMessage() . "\n");

    exit(1);
}

foreach ($changed as $path) {
    echo $path, "\n";
}

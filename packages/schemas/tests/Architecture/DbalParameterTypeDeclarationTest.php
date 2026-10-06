<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function dirname;

/**
 * Architectural convention test keeping DBAL's two parameter-type classes out of
 * every type position in shipped source.
 *
 * `Doctrine\DBAL\ParameterType` and `ArrayParameterType` are final classes of int
 * constants on DBAL 3 and native enums on DBAL 4. No package declares `doctrine/dbal`:
 * the omission rests on `doctrine/orm: ^3.7` hard-requiring `^3.8.2 || ^4` and on both
 * symbols carrying the same *name* across that range. A name is all it licenses — their
 * constants as values, a `match` subject, a `setParameter()` argument — never a declared
 * parameter, return or property type, and never a positive `instanceof`, because a
 * declared `: ParameterType` is a TypeError the moment an application resolves DBAL 3.
 *
 * Only DBAL 4 is installed here, so the suite is structurally blind to that break: this
 * test is the gate, in the shape .claude/rules/manifests.md gives every guarded soft dependency.
 */

/**
 * Whether the source puts either class anywhere but a constant read, its own
 * import or a negative `instanceof` guard.
 */
function dbalParameterTypeDeclarationIn(string $source): bool
{
    $allowed = preg_replace(
        [
            '/^\s*use\s+Doctrine\\\\DBAL\\\\(?:Array)?ParameterType;\s*$/m',
            '/\b(?:Array)?ParameterType::/',
            '/instanceof\s+(?:Array)?ParameterType\b/',
        ],
        '',
        $source,
    ) ?? $source;

    return preg_match('/\b(?:Array)?ParameterType\b/', $allowed) === 1;
}

it('names no DBAL parameter-type class in a type position', function (): void {
    $packagesDirectory = dirname(__DIR__, 3);
    $offenders = [];

    foreach (glob($packagesDirectory . '/*/src') ?: [] as $sourceDirectory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with((string) $file->getFilename(), '.php')) {
                continue;
            }

            if (dbalParameterTypeDeclarationIn((string) file_get_contents($file->getPathname()))) {
                $offenders[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBe(
        [],
        'Read Doctrine\DBAL\ParameterType and ArrayParameterType as constants only. They are int-constant '
        . 'classes on DBAL 3 and enums on DBAL 4, and no package declares doctrine/dbal — so a declared '
        . 'parameter, return or property type of either is a TypeError for every application that resolves '
        . 'DBAL 3, which nothing in this repo can run. Inline the expression instead of typing a helper.',
    );
});

it('sees a type position and looks past a constant, an import and a guard', function (string $source, bool $declared): void {
    expect(dbalParameterTypeDeclarationIn($source))->toBe($declared);
})->with([
    'a return type' => ['private static function bindingType(): ParameterType', true],
    'a nullable return type' => ['function typeOf(): ?ArrayParameterType', true],
    'a parameter type' => ['function bind(ParameterType $type): void', true],
    'a property type' => ['private ArrayParameterType $type;', true],
    'a union' => ['function typeOf(): ParameterType|int', true],
    'a docblock type' => ['/** @return ParameterType|int */', true],
    'its import' => ['use Doctrine\DBAL\ParameterType;', false],
    'a constant read' => ['$query->setParameter($name, $value, ParameterType::INTEGER);', false],
    'an array constant read' => ['$type = ArrayParameterType::STRING;', false],
    'an instanceof guard, which is a value test rather than a declaration' => ['if (!$type instanceof ParameterType && !$type instanceof ArrayParameterType) {', false],
]);

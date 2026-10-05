<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function dirname;

/**
 * Architectural convention test keeping every `AssociationReader` in shipped source the container's.
 *
 * The reader is a service an application may subclass or decorate (`sylament_schemas.association_reader`). A site
 * that needs it bound to another entity manager rebinds the reader it already holds with `withEntityManager()`, a
 * clone that keeps the subclass, where `new AssociationReader($manager)` builds the base class and drops the
 * application's in silence. The one construction left is the manager-less fallback a component built outside the
 * container takes.
 */

/**
 * Whether the source constructs an `AssociationReader`, by its short or its qualified name.
 */
function associationReaderConstructionIn(string $source): bool
{
    return preg_match('/\bnew\s+\\\\?(?:\w+\\\\)*AssociationReader\s*\(/', $source) === 1;
}

it('constructs an AssociationReader only in the manager-less fallback', function (): void {
    $packagesDirectory = dirname(__DIR__, 3);
    $constructors = [];

    foreach (glob($packagesDirectory . '/*/src') ?: [] as $sourceDirectory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with((string) $file->getFilename(), '.php')) {
                continue;
            }

            if (associationReaderConstructionIn((string) file_get_contents($file->getPathname()))) {
                $constructors[] = str_replace($packagesDirectory . '/', '', $file->getPathname());
            }
        }
    }

    sort($constructors);

    expect($constructors)->toBe(
        ['schemas/src/Components/Concerns/CanAccessAssociations.php'],
        'Rebind the reader the site already holds — the mounted schema\'s, the action\'s or the table\'s — with '
        . 'withEntityManager() instead of constructing one. AssociationReader is a service an application may '
        . 'subclass or decorate, and new AssociationReader(...) drops it in silence. The one construction is the '
        . 'manager-less fallback in CanAccessAssociations.',
    );
});

it('sees a construction and looks past a reference', function (string $source, bool $constructs): void {
    expect(associationReaderConstructionIn($source))->toBe($constructs);
})->with([
    'a construction on a manager' => ['new AssociationReader($manager)', true],
    'the manager-less fallback' => ['return $this->associationReader ??= new AssociationReader();', true],
    'a fully qualified construction' => ['new \Sylament\Schemas\State\AssociationReader($entityManager)', true],
    'a partly qualified construction' => ['new State\AssociationReader($entityManager)', true],
    'spacing around the name' => ['new  AssociationReader ($entityManager)', true],
    'a rebind' => ['$schema->getAssociationReader()->withEntityManager($manager)', false],
    'a class constant' => ['AssociationReader::class', false],
    'its import' => ['use Sylament\Schemas\State\AssociationReader;', false],
    'a longer class name' => ['new AssociationReaderSpy($entityManager)', false],
    'a docblock reference' => ['/** @see AssociationReader::withEntityManager() */', false],
]);

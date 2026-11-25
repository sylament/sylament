<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture;

use Sylament\Tests\Architecture\Source\ShippedSource;

use function dirname;

/**
 * Architectural convention test keeping shipped source from asking a registry for its default manager or connection,
 * since an application may map its classes on several managers; the reasoning is in docs/internals/php.md.
 */

it('asks the registry for a manager or a connection only by name or by class', function (): void {
    $packagesDirectory = dirname(__DIR__, 2) . '/packages';
    $callers = array_values(array_filter(
        ShippedSource::files(),
        static fn (string $file): bool => ShippedSource::callsRegistryDefault((string) file_get_contents($packagesDirectory . '/' . $file)),
    ));

    expect($callers)->toBe([]);
});

it('spots a registry asked for its default', function (): void {
    expect(ShippedSource::callsRegistryDefault('<?php $registry->getManager();'))->toBeTrue()
        ->and(ShippedSource::callsRegistryDefault('<?php $this->managerRegistry->getConnection();'))->toBeTrue()
        ->and(ShippedSource::callsRegistryDefault('<?php $manager->getConnection(); $registry->getManager("secondary");'))->toBeFalse();
});

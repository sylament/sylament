<?php

declare(strict_types=1);

use Sylament\Panels\Exceptions\NoDefaultPanelSetException;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;

function registryWith(PanelProvider ...$providers): PanelRegistry
{
    return new PanelRegistry($providers);
}

function providerFor(string $id, bool $default = false): PanelProvider
{
    return new class ($id, $default) extends PanelProvider {
        public function __construct(
            private readonly string $id,
            private readonly bool $default,
        ) {
        }

        public function panel(Panel $panel): Panel
        {
            return $panel->id($this->id)->path($this->id)->default($this->default);
        }
    };
}

it('builds panels keyed by id and memoises', function (): void {
    $registry = registryWith(providerFor('admin'), providerFor('app'));

    expect(array_keys($registry->all()))->toBe(['admin', 'app'])
        ->and($registry->get('admin'))->toBe($registry->get('admin'))
        ->and($registry->get('missing'))->toBeNull()
        ->and($registry->get(null))->toBeNull();
});

it('throws when two providers register the same id', function (): void {
    registryWith(providerFor('admin'), providerFor('admin'))->all();
})->throws(LogicException::class);

it('returns the default panel or throws', function (): void {
    $registry = registryWith(providerFor('admin'), providerFor('app', default: true));

    expect($registry->getDefault()->getId())->toBe('app');

    registryWith(providerFor('admin'))->getDefault();
})->throws(NoDefaultPanelSetException::class);

it('remembers which provider produced which panel', function (): void {
    $provider = providerFor('admin');

    expect(registryWith($provider)->getProviderClass('admin'))->toBe($provider::class);
});

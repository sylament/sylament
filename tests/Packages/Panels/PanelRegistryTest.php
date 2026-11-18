<?php

declare(strict_types=1);

use Sylament\Panels\Exceptions\NoDefaultPanelSetException;
use Sylament\Panels\Panel;
use Sylament\Panels\PanelProvider;
use Sylament\Panels\PanelRegistry;
use Sylament\Tests\Fixtures\Panels\AdminPanelProvider;

it("builds each tagged provider's panel once, keyed by its id", function (): void {
    $registry = $this::getContainer()->get(PanelRegistry::class);

    $admin = $registry->all()['admin'];

    expect($admin->getId())->toBe('admin')
        ->and($registry->all()['admin'])->toBe($admin)
        ->and($registry->get('admin'))->toBe($admin);
});

it('refuses two providers claiming one id, naming both', function (): void {
    $twin = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('admin')->path('admin-twin');
        }
    };

    expect(fn (): array => new PanelRegistry([new AdminPanelProvider(), $twin])->all())->toThrow(
        LogicException::class,
        sprintf('Panel "admin" is registered by both %s and %s.', AdminPanelProvider::class, $twin::class),
    );
});

it('answers a panel by its exact id only', function (): void {
    $registry = $this::getContainer()->get(PanelRegistry::class);

    expect($registry->get('admin')?->getId())->toBe('admin')
        ->and($registry->get('Admin'))->toBeNull()
        ->and($registry->get('missing'))->toBeNull()
        ->and($registry->get(null))->toBeNull();
});

it('answers the panel marked default(), and refuses a registry where none is', function (): void {
    $plain = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-plain');
        }
    };
    $marked = new class extends PanelProvider {
        public function panel(Panel $panel): Panel
        {
            return $panel->id('panel-marked')->default();
        }
    };

    expect(new PanelRegistry([$plain, $marked])->getDefault()->getId())->toBe('panel-marked')
        ->and(fn (): Panel => new PanelRegistry([$plain])->getDefault())->toThrow(
            NoDefaultPanelSetException::class,
            'No default panel is set. You may do this with the `default()` method inside a panel provider.',
        );
});

it('answers the provider class that built a panel, and refuses an unknown id', function (): void {
    $registry = $this::getContainer()->get(PanelRegistry::class);

    expect($registry->getProviderClass('admin'))->toBe(AdminPanelProvider::class)
        ->and(fn (): string => $registry->getProviderClass('missing'))->toThrow(LogicException::class, 'No panel with the ID "missing" is registered.');
});

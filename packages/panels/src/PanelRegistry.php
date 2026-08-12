<?php

declare(strict_types=1);

namespace Sylament\Panels;

use LogicException;
use Sylament\Panels\Exceptions\NoDefaultPanelSetException;

class PanelRegistry
{
    /**
     * @var array<string, Panel>|null
     */
    private ?array $panels = null;

    /**
     * @var array<string, class-string<PanelProvider>>
     */
    private array $providerClasses = [];

    /**
     * @param iterable<PanelProvider> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    /**
     * @return array<string, Panel>
     */
    public function all(): array
    {
        if ($this->panels !== null) {
            return $this->panels;
        }

        $panels = [];

        foreach ($this->providers as $provider) {
            $panel = $provider->panel(Panel::make());
            $id = $panel->getId();

            if (isset($panels[$id])) {
                throw new LogicException(sprintf(
                    'Panel "%s" is registered by both %s and %s.',
                    $id,
                    $this->providerClasses[$id],
                    $provider::class,
                ));
            }

            $panels[$id] = $panel;
            $this->providerClasses[$id] = $provider::class;
        }

        return $this->panels = $panels;
    }

    public function get(?string $id): ?Panel
    {
        if ($id === null) {
            return null;
        }

        return $this->all()[$id] ?? null;
    }

    public function getDefault(): Panel
    {
        foreach ($this->all() as $panel) {
            if ($panel->isDefault()) {
                return $panel;
            }
        }

        throw new NoDefaultPanelSetException('No default panel is set. You may do this with the `default()` method inside a panel provider.');
    }

    /**
     * @return class-string<PanelProvider>
     */
    public function getProviderClass(string $id): string
    {
        $this->all();

        return $this->providerClasses[$id] ?? throw new LogicException(sprintf('No panel with the ID "%s" is registered.', $id));
    }
}

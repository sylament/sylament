<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

trait BelongsToContainer
{
    protected Schema $container;

    protected Schema $rootContainer;

    public function container(Schema $schema): static
    {
        $this->container = $schema;

        return $this;
    }

    public function getContainer(): Schema
    {
        return $this->container;
    }

    public function getRootContainer(): Schema
    {
        return $this->rootContainer ??= (function (): Schema {
            $container = $this->getContainer();

            while (($parentComponent = $container->getParentComponent()) !== null) {
                $container = $parentComponent->getContainer();
            }

            return $container;
        })();
    }

    public function getLiveComponent(): InteractsWithSchemas
    {
        return $this->getContainer()->getLiveComponent();
    }

    /**
     * The nullable twin of {@see getLiveComponent()}, for callers probing an
     * optional binding (the action-side live-component chain) rather than
     * requiring one; also null while the container back-reference is unbound.
     */
    public function getLiveComponentOrNull(): ?InteractsWithSchemas
    {
        return isset($this->container) ? $this->container->getLiveComponentOrNull() : null;
    }
}

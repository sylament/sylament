<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

trait BelongsToSchemaComponent
{
    protected ?Component $schemaComponent = null;

    protected ?Schema $schemaContainer = null;

    public function schemaComponent(?Component $component): static
    {
        $this->schemaComponent = $component;

        return $this;
    }

    public function schemaContainer(?Schema $schema): static
    {
        $this->schemaContainer = $schema;

        return $this;
    }

    public function getSchemaComponent(): ?Component
    {
        return $this->schemaComponent ?? $this->getSchemaContainer()?->getParentComponent() ?? $this->getGroup()?->getSchemaComponent();
    }

    public function getSchemaComponentOrFail(): Component
    {
        return $this->getSchemaComponent() ?? throw new LogicException(static::class . ' has no schema component: none was resolved from the action itself, its schema container, or its group. The [$get] and [$set] closure utilities are only available to an action rendered inside a schema.');
    }

    public function getSchemaContainer(): ?Schema
    {
        return $this->schemaContainer ?? $this->getGroup()?->getSchemaContainer();
    }
}

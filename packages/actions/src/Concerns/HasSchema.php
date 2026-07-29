<?php

declare(strict_types=1);

namespace Sylament\Actions\Concerns;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Schema;

trait HasSchema
{
    /**
     * @var Schema|array<Component|Action|ActionGroup>|Closure|null
     */
    protected Schema|array|Closure|null $schema = null;

    protected bool|Closure $isSchemaDisabled = false;

    protected bool|Closure|null $hasFormWrapper = null;

    /**
     * @param  array<Component|Action|ActionGroup>|Closure|null  $schema
     */
    public function schema(array|Closure|null $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function disabledSchema(bool|Closure $condition = true): static
    {
        $this->isSchemaDisabled = $condition;

        return $this;
    }

    public function isSchemaDisabled(): bool
    {
        return (bool) $this->evaluate($this->isSchemaDisabled);
    }

    public function getSchema(Schema $schema): ?Schema
    {
        $modifiedSchema = $this->evaluate($this->schema, [
            'form' => $schema,
            'schema' => $schema,
            'infolist' => $schema,
        ]);

        if ($modifiedSchema === null) {
            return null;
        }

        if ($modifiedSchema === []) {
            return null;
        }

        if (is_array($modifiedSchema)) {
            $modifiedSchema = $schema->components($modifiedSchema);
        }

        if ($this->isSchemaDisabled()) {
            return $modifiedSchema->disabled();
        }

        return $modifiedSchema;
    }

    public function formWrapper(bool|Closure|null $condition = true): static
    {
        $this->hasFormWrapper = $condition;

        return $this;
    }

    public function hasFormWrapper(): bool
    {
        return (bool) ($this->evaluate($this->hasFormWrapper) ?? true);
    }
}

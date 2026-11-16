<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;

/**
 * A host outside any request whose methods give each schema-name lookup one shape to
 * resolve: two declared schemas, one built by a `<name>Schema()` method, a name with
 * both methods, an undeclared schema method, a parameterless getter, and methods the
 * gate refuses. Every one of them records its call in the {@see HookLog} it is given.
 */
class SchemaResolutionHost implements InteractsWithSchemas
{
    use HasSchemas;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function schemas(): array
    {
        return ['form', 'settings'];
    }

    public function form(Schema $schema): Schema
    {
        $this->hookLog->record('form()');

        return $schema;
    }

    public function settingsSchema(Schema $schema): Schema
    {
        $this->hookLog->record('settingsSchema()');

        return $schema;
    }

    public function notes(Schema $schema): Schema
    {
        $this->hookLog->record('notes()');

        return $schema;
    }

    public function notesSchema(Schema $schema): Schema
    {
        $this->hookLog->record('notesSchema()');

        return $schema;
    }

    public function draft(Schema $schema): Schema
    {
        $this->hookLog->record('draft()');

        return $schema;
    }

    public function preview(): Schema
    {
        $this->hookLog->record('preview()');

        return $this->makeSchema();
    }

    public function title(Schema $schema): string
    {
        $this->hookLog->record('title()');

        return 'Title';
    }

    public function heading(): string
    {
        $this->hookLog->record('heading()');

        return 'Heading';
    }

    public function caption(string $text): string
    {
        $this->hookLog->record('caption()');

        return $text;
    }

    public function pair(Schema $schema, string $label): Schema
    {
        $this->hookLog->record('pair()');

        return $schema->key($label);
    }
}

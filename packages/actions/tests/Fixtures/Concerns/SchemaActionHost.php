<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Concerns;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\LiveResponder;

/**
 * A schema-hosting action host: `publish` is a direct child of the declared
 * `schema` and `archive` is component-registered on its `Box` section, so both
 * `resolveSchemaComponentAction()` paths — root lookup and nested container
 * key — resolve against the same schema.
 */
class SchemaActionHost implements InteractsWithActions, InteractsWithSchemas
{
    use HasActions;
    use HasSchemas;

    public ?string $published = null;

    public function __construct()
    {
        $this->setLiveResponder(new LiveResponder());
    }

    public function schema(Schema $schema): Schema
    {
        return $schema->components([
            Action::make('publish')->action(function (SchemaActionHost $liveComponent): string {
                $liveComponent->published = 'published';

                return 'published';
            }),
            Section::make('Box')->registerActions([
                Action::make('archive')->action(fn (): string => 'archived'),
            ]),
        ]);
    }
}

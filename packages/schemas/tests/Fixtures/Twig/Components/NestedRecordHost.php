<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Components\Section;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Schemas\Tests\Fixtures\State\Author;
use Sylament\Schemas\Tests\Fixtures\State\Post;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The nested counterpart of {@see RecordHost}: the seeded record owns an
 * {@see Author}, and a section-scoped required field puts the mapping edges on a
 * nested path so the round-trip and the validation-throws-first arm can be
 * exercised through the real container.
 */
#[AsLiveComponent(name: 'NestedRecordHost', template: '@SchemasFixtures/components/NestedRecordHost.html.twig')]
class NestedRecordHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $data = [];

    private Post $record;

    public function __construct()
    {
        $record = new Post();
        $record->setTitle('Seeded');
        $record->setAuthor(new Author(name: 'Ada', handle: '@ada'));

        $this->record = $record;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record($this->record)
            ->components([
                ConstrainedField::make('title'),
                Section::make('Author')->statePath('author')->components([
                    ConstrainedField::make('name')->required(),
                ]),
            ]);
    }
}

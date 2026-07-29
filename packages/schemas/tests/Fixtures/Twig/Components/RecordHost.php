<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\Tests\Fixtures\Components\ConstrainedField;
use Sylament\Schemas\Tests\Fixtures\Concerns\RecordFixture;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host bound to a seeded record instance, exercising the
 * `fill($record)` → edit bag → `getData()` round-trip through the real container
 * with a required field on top. The record is a plain property, not a LiveProp:
 * an arbitrary object has no LiveProp hydration, and nothing here crosses a
 * request boundary.
 */
#[AsLiveComponent(name: 'RecordHost', template: '@SchemasFixtures/components/RecordHost.html.twig')]
class RecordHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /** @var array<string, mixed> */
    #[LiveProp(writable: true)]
    public array $data = [];

    private RecordFixture $record;

    public function __construct()
    {
        $record = new RecordFixture();
        $record->setTitle('Seeded');

        $this->record = $record;
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record($this->record)
            ->components([
                ConstrainedField::make('title')->required(),
                ConstrainedField::make('perex'),
            ]);
    }
}

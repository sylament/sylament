<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Twig\Components;

use Sylament\Schemas\Utilities\Set;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host exercising T2 end-to-end: a writable `$data` bag, four fields
 * spanning the binding-modifier matrix (live / default / lazy / debounce), and a
 * `title` whose `afterStateUpdated` mirrors its previous value into `slug`.
 */
#[AsLiveComponent(name: 'LiveStateHost', template: '@SchemasFixtures/components/LiveStateHost.html.twig')]
class LiveStateHost implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The bag assembled by `getState()` on `save`, exposed as a LiveProp so it
     * survives the round-trip and can be read back in the harness.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    public function mount(): void
    {
        // A live host seeds its bag at mount (Filament forms call fill()) so the
        // client-side controller accepts data-model="data.<field>" nested writes.
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                LiveField::make('title')
                    ->live()
                    ->afterStateUpdated(fn (mixed $old, Set $set): mixed => $set('slug', $old)),
                LiveField::make('slug'),
                LiveField::make('heading')
                    ->lazy(),
                LiveField::make('excerpt')
                    ->debounce(250),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}

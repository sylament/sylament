<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\KeyValue;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying one reorderable `KeyValue`, so its three
 * structural actions run through the real mounted-action pipeline and the row
 * list they mutate survives the round-trip in `$data`.
 *
 * `$stateUpdates` counts the field's `afterStateUpdated` firings and `$lastState`
 * records the value each of them was handed — the map, never the row list.
 */
#[AsLiveComponent(name: 'KeyValueForm', template: '@FormsFixtures/components/KeyValueForm.html.twig')]
class KeyValueForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    #[LiveProp]
    public int $stateUpdates = 0;

    /**
     * @var array<array-key, mixed>|null
     */
    #[LiveProp]
    public ?array $lastState = null;

    /**
     * @param array<string, mixed>|null $data
     */
    public function mount(?array $data = null): void
    {
        $this->fill($data);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                KeyValue::make('meta')
                    ->reorderable()
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->stateUpdates++;
                        $this->lastState = is_array($state) ? $state : null;
                    }),
            ]);
    }
}

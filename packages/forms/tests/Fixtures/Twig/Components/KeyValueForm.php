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
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host carrying one reorderable `KeyValue`, so its three
 * structural actions run through the real mounted-action pipeline and the row
 * list they mutate survives the round-trip in `$data`.
 *
 * `$stateUpdates` counts the field's `afterStateUpdated` firings and `$lastState`
 * records the value each of them was handed — the map, never the row list, and
 * `$disabled` disables the field so a test can mount an action against one.
 *
 * `deleteFirstMetaRow()` exists because `TestLiveComponent::call()` sends no
 * dirty model writes and `submitForm()` sends no action arguments: an
 * argument-less action is the only way to drive a cell write and a structural
 * action in one request, which is the ordering the field's index naming rests on.
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
    public bool $disabled = false;

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
    public function mount(?array $data = null, bool $disabled = false): void
    {
        $this->disabled = $disabled;

        $this->fill($data);
    }

    #[LiveAction]
    public function deleteFirstMetaRow(): mixed
    {
        return $this->mountAction('delete', ['row' => 0], ['schemaComponent' => 'schema.meta']);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                KeyValue::make('meta')
                    ->reorderable()
                    ->disabled(fn (): bool => $this->disabled)
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->stateUpdates++;
                        $this->lastState = is_array($state) ? $state : null;
                    }),
            ]);
    }
}

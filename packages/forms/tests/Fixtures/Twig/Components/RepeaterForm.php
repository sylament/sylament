<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A live host carrying a single repeater, so the structural actions run through
 * the real mounted-action pipeline: `mountAction` resolves them from the
 * `schemaComponent` context, runs them (they open no modal), and the mutated bag
 * survives the round-trip in `$data`. The template renders no schema — the
 * repeater's own partials arrive with the render task — so the round-trip
 * exercises the action pipeline alone.
 *
 * The surfaces each test needs are props rather than separate fixtures: `$data`
 * seeds the items, `$stateUpdates` counts the repeater's `afterStateUpdated`
 * firings, and the three flags toggle the chrome gates.
 */
#[AsLiveComponent(name: 'RepeaterForm', template: '@FormsFixtures/components/RepeaterForm.html.twig')]
class RepeaterForm implements InteractsWithActions, InteractsWithSchemas
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

    #[LiveProp]
    public bool $isCloneable = false;

    #[LiveProp]
    public bool $isReorderableWithButtons = false;

    #[LiveProp]
    public bool $isDisabled = false;

    /**
     * @param array<string, mixed>|null $data
     */
    public function mount(?array $data = null): void
    {
        // fill(null) applies the repeater's defaultItems(1) default; a seeded bag
        // is filled as state instead, so the seeded items are what gets re-keyed.
        $this->fill($data);
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('tags')
                    ->components([TextInput::make('name')->default('x')])
                    ->cloneable($this->isCloneable)
                    ->reorderableWithButtons($this->isReorderableWithButtons)
                    ->disabled($this->isDisabled)
                    ->afterStateUpdated(function (): void {
                        $this->stateUpdates++;
                    }),
            ]);
    }
}

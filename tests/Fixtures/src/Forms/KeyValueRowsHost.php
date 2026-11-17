<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Checkbox;
use Sylament\Forms\Components\KeyValue;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Key-value fields a user edits through their actions: `pairs`, reorderable, whose
 * `afterStateUpdated` hook records the state it reads in the request's {@see HookLog};
 * `locked`, reorderable and disabled; and `gated`, addable only while the `allowAdd`
 * checkbox beside it is ticked. `mount()` fills the form from the record it is given.
 */
#[AsLiveComponent(name: 'Forms:KeyValueRowsHost', template: '@Fixtures/forms/KeyValueRowsHost.html.twig')]
class KeyValueRowsHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    /**
     * @param array<string, mixed> $record
     */
    public function mount(array $record = []): void
    {
        $this->getSchema('form')->fill($record);
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                KeyValue::make('pairs')
                    ->reorderable()
                    ->afterStateUpdated(function (mixed $state): void {
                        $this->hookLog->record('pairs: ' . json_encode($state, JSON_THROW_ON_ERROR));
                    }),
                KeyValue::make('locked')->reorderable()->disabled(),
                Checkbox::make('allowAdd'),
                KeyValue::make('gated')->addable(static fn (Get $get): bool => (bool) $get('allowAdd')),
            ]);
    }
}

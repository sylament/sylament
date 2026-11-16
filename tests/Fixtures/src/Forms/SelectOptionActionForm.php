<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Select;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form bound to a {@see Memo} whose selects have a static map and no relationship, with
 * both option forms: the single one resolves its selected record by a closure, and its
 * create and update closures record in the request's {@see HookLog} the record their modal
 * is bound to and the subject submitted; the multiple one creates an option it already
 * lists.
 */
#[AsLiveComponent(name: 'Forms:SelectOptionActionForm', template: '@Fixtures/forms/SelectOptionActionForm.html.twig')]
class SelectOptionActionForm implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public const string RECORD_SUBJECT = 'Notebook';

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

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record(new Memo(self::RECORD_SUBJECT))
            ->components([
                Select::make('plant')
                    ->options(['fern' => 'Fern', 'moss' => 'Moss'])
                    ->getSelectedRecordUsing(static fn (string $state): Memo => new Memo(ucfirst($state)))
                    ->createOptionForm([TextInput::make('subject')])
                    ->createOptionUsing(function (Schema $schema, array $data): string {
                        $this->recordWrite('create', $schema, $data);

                        return 'moss';
                    })
                    ->editOptionForm([TextInput::make('subject')])
                    ->updateOptionUsing(function (Schema $schema, array $data): void {
                        $this->recordWrite('update', $schema, $data);
                    }),
                Select::make('plants')
                    ->multiple()
                    ->options(['fern' => 'Fern', 'moss' => 'Moss'])
                    ->manageOptionForm([TextInput::make('subject')])
                    ->createOptionUsing(static fn (): string => 'moss'),
            ]);
    }

    /**
     * @param array<mixed> $data
     */
    private function recordWrite(string $write, Schema $schema, array $data): void
    {
        $record = $schema->getRecord();

        $this->hookLog->record(sprintf('%s on %s: %s', $write, $record instanceof Memo ? $record->subject : get_debug_type($record), is_string($data['subject'] ?? null) ? $data['subject'] : ''));
    }
}

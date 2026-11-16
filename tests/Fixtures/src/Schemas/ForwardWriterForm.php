<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose two writers each sit before the field they write: `loud` asks for its
 * target's update hooks and `quiet` does not, and each target records its hook in the
 * request's {@see HookLog}.
 */
#[AsLiveComponent(name: 'Schemas:ForwardWriterForm', template: '@Fixtures/schemas/ForwardWriterForm.html.twig')]
class ForwardWriterForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
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

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('loud')
                    ->afterStateUpdated(static fn (Set $set, ?string $state): mixed => $set('echoed', mb_strtolower((string) $state), shouldCallUpdatedHooks: true)),
                TextInput::make('quiet')
                    ->afterStateUpdated(static fn (Set $set, ?string $state): mixed => $set('muted', mb_strtolower((string) $state))),
                TextInput::make('echoed')
                    ->afterStateUpdated(function (?string $state): void {
                        $this->hookLog->record("echoed: {$state}");
                    }),
                TextInput::make('muted')
                    ->afterStateUpdated(function (?string $state): void {
                        $this->hookLog->record("muted: {$state}");
                    }),
            ]);
    }
}

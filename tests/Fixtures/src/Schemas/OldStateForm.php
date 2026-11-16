<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose one field reads its state through two {@see TaggingStateCast}s and records,
 * in the request's {@see HookLog}, the value it held before the request changed it, read
 * through its casts and as stored.
 */
#[AsLiveComponent(name: 'Schemas:OldStateForm', template: '@Fixtures/schemas/OldStateForm.html.twig')]
class OldStateForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = ['motto' => 'Before'];

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
                TextInput::make('motto')
                    ->stateCast(TaggingStateCast::First)
                    ->stateCast(TaggingStateCast::Second)
                    ->afterStateUpdated(function (?string $old, mixed $oldRaw): void {
                        $this->hookLog->record("old: {$old}; raw: " . var_export($oldRaw, true));
                    }),
            ]);
    }
}

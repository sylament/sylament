<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Affix actions reading the state of the field they sit on: a suffix action recording it
 * in the request's {@see HookLog}, and copy actions whose message and duration describe it,
 * on a field and on a field inside a repeater item.
 */
#[AsLiveComponent(name: 'Forms:AffixActionForm', template: '@Fixtures/forms/AffixActionForm.html.twig')]
class AffixActionForm implements InteractsWithActions, InteractsWithSchemas
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

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('code')->suffixAction(
                    Action::make('echo')->action(function (mixed $state): void {
                        $this->hookLog->record('echo: ' . (is_string($state) ? $state : get_debug_type($state)));
                    }),
                ),
                TextInput::make('copied')->copyable(
                    copyMessage: static fn (mixed $state): string => is_string($state) ? "Copied {$state}" : 'Not the field state',
                    copyMessageDuration: static fn (mixed $state): int => is_string($state) ? mb_strlen($state) * 1000 : 0,
                ),
                Repeater::make('tags')->reorderable(false)->components([
                    TextInput::make('name')->copyable(
                        copyMessage: static fn (mixed $state): string => is_string($state) ? "Copied {$state}" : 'Not the field state',
                    ),
                ]),
            ]);
    }
}

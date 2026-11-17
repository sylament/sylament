<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host that hands every action one form, a `shared` field when the form, schema and
 * infolist it is given are one container and a `mismatched` field otherwise; `inherits`
 * declares no form, `own` declares its own and `none` an empty one, each recording its data
 * in the request's {@see HookLog} when it runs.
 */
#[AsLiveComponent(name: 'Actions:FallbackFormHost', template: '@Fixtures/actions/FallbackFormHost.html.twig')]
class FallbackFormHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    public function __construct(
        private readonly HookLog $hookLog,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return static fn (Schema $schema, Schema $form, Schema $infolist): array => [
            TextInput::make(($schema === $form && $form === $infolist) ? 'shared' : 'mismatched'),
        ];
    }

    public function inheritsAction(): Action
    {
        return $this->recording('inherits');
    }

    public function ownAction(): Action
    {
        return $this->recording('own')->schema([
            TextInput::make('own'),
        ]);
    }

    public function noneAction(): Action
    {
        return $this->recording('none')->schema([]);
    }

    private function recording(string $name): Action
    {
        return Action::make($name)->action(function (array $data) use ($name): void {
            $this->hookLog->record("{$name}: " . json_encode($data, JSON_THROW_ON_ERROR));
        });
    }
}

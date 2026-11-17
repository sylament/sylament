<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A host answering its mounted schemas' record hook with `Product`'s class, as a list page
 * answers its resource's: `register` names no record class and holds a `unique()` name.
 */
#[AsLiveComponent(name: 'Actions:SchemaRecordHost', template: '@Fixtures/actions/SchemaRecordHost.html.twig')]
class SchemaRecordHost implements InteractsWithActions, InteractsWithSchemas
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

    public function registerAction(): Action
    {
        return Action::make('register')
            ->schema([
                TextInput::make('name')->unique(),
            ])
            ->action(function (array $data): void {
                $this->hookLog->record('register: ' . json_encode($data, JSON_THROW_ON_ERROR));
            });
    }

    /**
     * @return class-string
     */
    protected function getMountedActionSchemaRecord(): string
    {
        return Product::class;
    }
}

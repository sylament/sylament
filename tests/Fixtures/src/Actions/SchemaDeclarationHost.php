<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A host whose actions declare their modal schemas each way, each recording its name and data
 * in the request's {@see HookLog} when it runs: a list, a closure handing back a prepared
 * schema, a declaration cleared to `null`, an empty list and a closure answering one, a
 * disabled schema, one without its form wrapper, one whose `before()` reads the mounted
 * schema beside a confirmation that reads it too, one with content and a content footer
 * around its schema, and `retitle`, a {@see CappedHeadline} already holding a slug another
 * headline took, whose form maps only its code and title and whose body maps the form onto
 * the record, recording its name only once that passes.
 */
#[AsLiveComponent(name: 'Actions:SchemaDeclarationHost', template: '@Fixtures/actions/SchemaDeclarationHost.html.twig')]
class SchemaDeclarationHost implements InteractsWithActions, InteractsWithSchemas
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

    public function listedAction(): Action
    {
        return $this->recording('listed')->schema([
            TextInput::make('title'),
        ]);
    }

    public function preparedAction(): Action
    {
        return $this->recording('prepared')->schema(fn (Schema $schema): Schema => $schema->components([
            TextInput::make('note'),
        ]));
    }

    public function clearedAction(): Action
    {
        return $this->recording('cleared')
            ->schema([
                TextInput::make('gone'),
            ])
            ->schema(null);
    }

    public function emptyListAction(): Action
    {
        return $this->recording('emptyList')->schema([]);
    }

    public function emptyClosureAction(): Action
    {
        return $this->recording('emptyClosure')->schema(fn (): array => []);
    }

    public function lockedAction(): Action
    {
        return $this->recording('locked')
            ->schema([
                TextInput::make('title'),
            ])
            ->disabledSchema();
    }

    public function unwrappedAction(): Action
    {
        return $this->recording('unwrapped')
            ->schema([
                TextInput::make('title'),
            ])
            ->formWrapper(false);
    }

    public function readingAction(): Action
    {
        return $this->recording('reading')
            ->schema([
                TextInput::make('title'),
            ])
            ->before(function (Action $action): void {
                $this->hookLog->record('before: ' . json_encode($action->getMountedSchema()?->getState(), JSON_THROW_ON_ERROR));
            });
    }

    public function confirmReadingAction(): Action
    {
        return $this->recording('confirmReading')
            ->requiresConfirmation()
            ->before(function (Action $action): void {
                $this->hookLog->record('before: ' . json_encode($action->getMountedSchema()?->getState(), JSON_THROW_ON_ERROR));
            });
    }

    public function layeredAction(): Action
    {
        return $this->recording('layered')
            ->modalContent(new Markup('<p data-slot="content">Before the form</p>', 'UTF-8'))
            ->schema([
                TextInput::make('title'),
            ])
            ->modalContentFooter(new Markup('<p data-slot="footer">After the form</p>', 'UTF-8'));
    }

    public function retitleAction(): Action
    {
        return Action::make('retitle')
            ->record(new CappedHeadline(code: 'H2', slug: 'taken'))
            ->schema([
                TextInput::make('code'),
                TextInput::make('title'),
            ])
            ->action(function (Schema $schema): void {
                $schema->getData();

                $this->hookLog->record('retitle');
            });
    }

    private function recording(string $name): Action
    {
        return Action::make($name)->action(function (array $data) use ($name): void {
            $this->hookLog->record("{$name}: " . json_encode($data, JSON_THROW_ON_ERROR));
        });
    }
}

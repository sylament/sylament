<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Set;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose actions fill it, read its state the two ways a schema answers it and tell
 * the browser to reset: a group greets the name its own field formats on a fill, a field
 * commits a stamp when the state is taken, and a field is never saved. What a read
 * answered is recorded in the request's {@see HookLog}.
 */
#[AsLiveComponent(name: 'Schemas:ProfileForm', template: '@Fixtures/schemas/ProfileForm.html.twig')]
class ProfileForm implements InteractsWithSchemas
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
                Group::make([
                    TextInput::make('name')
                        ->required()
                        ->formatStateUsing(static fn (?string $state): string => ucfirst((string) $state)),
                ])
                    ->statePath('author')
                    ->afterStateHydrated(static fn (?array $state, Set $set): mixed => $set('greeting', 'Hello ' . (is_string($name = $state['name'] ?? null) ? $name : 'nobody'))),
                TextInput::make('greeting'),
                TextInput::make('subject'),
                TextInput::make('stamp')
                    ->beforeStateDehydrated(static fn (TextInput $component): TextInput => $component->state('committed'), shouldUpdateValidatedStateAfter: true),
                TextInput::make('draft')->dehydrated(false),
            ]);
    }

    #[LiveAction]
    public function fillProfile(): void
    {
        $this->getSchema('form')->fill(['author' => ['name' => 'ada'], 'stamp' => 'draft', 'draft' => 'kept']);
    }

    #[LiveAction]
    public function fillWithoutAuthor(): void
    {
        $this->getSchema('form')->fill(['author' => null]);
    }

    #[LiveAction]
    public function fillFromMemo(): void
    {
        $this->getSchema('form')->fill(new Memo('Minutes'));
    }

    #[LiveAction]
    public function takeState(): void
    {
        $state = $this->getSchema('form')->getState(afterValidate: function (array $state): void {
            $this->hookLog->record('validated: ' . json_encode($state, JSON_THROW_ON_ERROR));
        });

        $this->hookLog->record('state: ' . json_encode($state, JSON_THROW_ON_ERROR));
    }

    #[LiveAction]
    public function takeSnapshot(): void
    {
        $this->hookLog->record('snapshot: ' . json_encode($this->getSchema('form')->getStateSnapshot(), JSON_THROW_ON_ERROR));
    }

    #[LiveAction]
    public function resetClientState(): void
    {
        $this->getSchema('form')->dispatchClientSideStateReset();
    }
}

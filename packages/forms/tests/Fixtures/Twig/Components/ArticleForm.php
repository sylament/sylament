<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\Textarea;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Components\Utilities\Set;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A shipped-shape live host proving real form fields consume the T2 live-state
 * loop: `title` binds `on(change)` (via `live(onBlur: true)`) and its
 * `afterStateUpdated` slugifies the new value into the sibling `slug` field; a
 * `Textarea` exercises F3. The whole slice — `Field` + `TextInput` + `Textarea`
 * — is driven end to end through the reactive round-trip here.
 */
#[AsLiveComponent(name: 'ArticleForm', template: '@FormsFixtures/components/ArticleForm.html.twig')]
class ArticleForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The bag assembled by `getState()` on `save`, exposed as a LiveProp so it
     * survives the round-trip and can be read back in the harness.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    public function mount(): void
    {
        // A live host seeds its bag at mount (Filament forms call fill()) so the
        // client-side controller accepts data-model="data.<field>" nested writes.
        $this->fill();
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set('slug', $this->slugify($state))),
                TextInput::make('slug'),
                Textarea::make('excerpt'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }

    private function slugify(?string $value): string
    {
        return new AsciiSlugger()->slug((string) $value)->lower()->toString();
    }
}

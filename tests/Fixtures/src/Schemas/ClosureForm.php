<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Stringable;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Get;
use Sylament\Schemas\State\Set;
use Sylament\Tests\Fixtures\Schemas\Record\Memo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form editing a {@see Memo}, whose fields label themselves, or write another field,
 * through a closure asking for one value by its name, typed so the by-type map cannot
 * answer it, or by its type under another name.
 */
#[AsLiveComponent(name: 'Schemas:ClosureForm', template: '@Fixtures/schemas/ClosureForm.html.twig')]
class ClosureForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = ['title' => 'Draft', 'remark' => ''];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->record(new Memo('Minutes'))
            ->operation('edit')
            ->statePath('data')
            ->components([
                TextInput::make('title')
                    ->label(static fn (?string $state, ?string $rawState): string => "State {$state}, raw {$rawState}")
                    ->afterStateUpdated(static fn (callable $set, ?string $state): mixed => $set('slug', "{$state} slug")),
                TextInput::make('slug'),
                TextInput::make('remark')
                    ->label(static fn ($rawState): string => ($rawState === '') ? 'Raw empty string' : 'Raw ' . get_debug_type($rawState)),
                TextInput::make('subtitle')
                    ->label(static fn (callable $get): string => 'Under ' . (is_string($title = $get('title')) ? $title : 'nothing'))
                    ->afterStateUpdated(static fn (Set $write, ?string $state): mixed => $write('summary', "{$state} summary")),
                TextInput::make('summary')
                    ->label(static fn (Get $read): string => 'Read ' . (is_string($title = $read('title')) ? $title : 'nothing')),
                TextInput::make('author')
                    ->label(static fn (object $record): string => 'By ' . (($record instanceof Memo) ? $record->subject : $record::class)),
                TextInput::make('stage')
                    ->label(static fn (string $operation, string $context): string => "{$operation} and {$context}"),
                TextInput::make('host')
                    ->label(static fn ($liveComponent): string => ($liveComponent instanceof self) ? 'This host' : 'Another host'),
                TextInput::make('memo')
                    ->label(static fn (Memo $note): string => "Memo {$note->subject}"),
                TextInput::make('note')
                    ->label(static fn (Stringable $note): string => "Note {$note}"),
            ]);
    }
}

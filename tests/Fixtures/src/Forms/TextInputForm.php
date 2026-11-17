<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\NotEqualTo;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Text inputs validated by each type sugar, by sugar whose condition resolves false, by
 * value bounds (set, blank-resolving, zero, beside `required()`), by `tel()` and `regex()`
 * in both call orders, and by every constraint arm at once; and text inputs cleaned by
 * `trim()` and `stripCharacters()` beside one cleaned by neither.
 */
#[AsLiveComponent(name: 'Forms:TextInputForm', template: '@Fixtures/forms/TextInputForm.html.twig')]
final class TextInputForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TextInput::make('email')->email(),
                TextInput::make('site')->url(),
                TextInput::make('lazy')->email(static fn (): bool => false)->tel(static fn (): bool => false)->url(static fn (): bool => false),
                TextInput::make('age')->integer(),
                TextInput::make('price')->numeric()->minValue(1)->maxValue(10),
                TextInput::make('quantity')->numeric()->maxValue(static fn (): string => ''),
                TextInput::make('delta')->numeric()->minValue(0),
                TextInput::make('amount')->required()->numeric()->minValue(1),
                TextInput::make('phone')->tel(),
                TextInput::make('custom_phone')->tel()->telRegex('/^\d+$/'),
                TextInput::make('phone_then_regex')->tel()->regex('/^x+$/'),
                TextInput::make('regex_then_phone')->regex('/^x+$/')->tel(),
                TextInput::make('ordered')
                    ->required()
                    ->minLength(5)
                    ->tel()
                    ->constraint(new NotEqualTo(value: 'ab', message: 'Pick another value.'))
                    ->email()
                    ->maxValue(0),
                TextInput::make('slug')->trim()->stripCharacters('-')->maxLength(2),
                TextInput::make('plain'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}

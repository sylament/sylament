<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Fields bounded by length each way the setters allow: both bounds, an exact length, a
 * numeric value under a cap, zero bounds, and both bounds with a message per bound.
 */
#[AsLiveComponent(name: 'Forms:LengthForm', template: '@Fixtures/forms/LengthForm.html.twig')]
final class LengthForm implements InteractsWithSchemas
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
                TextInput::make('bounded')->minLength(3)->maxLength(5),
                TextInput::make('exact')->length(4),
                TextInput::make('amount')->numeric()->maxLength(4),
                TextInput::make('zeroMax')->maxLength(0),
                TextInput::make('zeroExact')->length(0),
                TextInput::make('worded')
                    ->minLength(3)
                    ->maxLength(5)
                    ->validationMessages([Length::class => ['minMessage' => 'Say a little more.', 'maxMessage' => 'Keep it short.']]),
                TextInput::make('pin')
                    ->length(4)
                    ->validationMessages([Length::class => ['exactMessage' => 'A pin has four characters.']]),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}

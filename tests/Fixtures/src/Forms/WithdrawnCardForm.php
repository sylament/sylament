<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\Record\VettedCard;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form over a card whose title is withdrawn, mapping only its author's name: `save`
 * maps the form onto the card, whose own title constraint no component maps.
 */
#[AsLiveComponent(name: 'Forms:WithdrawnCardForm', template: '@Fixtures/forms/WithdrawnCardForm.html.twig')]
final class WithdrawnCardForm implements InteractsWithSchemas
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
        $card = new VettedCard();
        $card->title = VettedCard::WITHDRAWN;

        return $schema
            ->statePath('data')
            ->record($card)
            ->components([
                TextInput::make('author.name'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->getSchema('form')->getData();
    }
}

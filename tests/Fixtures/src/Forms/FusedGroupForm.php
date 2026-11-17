<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FusedGroup;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A labelled fused group of two fields, the first of which one value fails in two ways at
 * once and the second of which is required, with a slot above and below the group's
 * error list.
 */
#[AsLiveComponent(name: 'Forms:FusedGroupForm', template: '@Fixtures/forms/FusedGroupForm.html.twig')]
final class FusedGroupForm implements InteractsWithSchemas
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
                FusedGroup::make([
                    TextInput::make('first')->minLength(5)->regex('/^\d+$/'),
                    TextInput::make('second')->required(),
                ])
                    ->key('contact')
                    ->label('Contact')
                    ->aboveErrorMessage('Above the errors')
                    ->belowErrorMessage('Below the errors'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->validateSchema($this->getSchema('form'));
    }
}

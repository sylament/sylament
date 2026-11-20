<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\Repeater;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A repeater holding one item of two fields, which a test adds a second item to; each
 * field records its update hook with its new value in the request's {@see HookLog}.
 */
#[AsLiveComponent(name: 'Forms:AddedItemForm', template: '@Fixtures/forms/AddedItemForm.html.twig')]
class AddedItemForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [
        'items' => [
            'first' => ['name' => 'One', 'unit' => 'g'],
        ],
    ];

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
                Repeater::make('items')->reorderable(false)->components([
                    TextInput::make('name')
                        ->afterStateUpdated(function (?string $state): void {
                            $this->hookLog->record("name: {$state}");
                        }),
                    TextInput::make('unit')
                        ->afterStateUpdated(function (?string $state): void {
                            $this->hookLog->record("unit: {$state}");
                        }),
                ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Schemas\Components\Actions;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A record-bound form whose only action lives inside it and which registers
 * **no** `resolveRecordUsing()` callback — the shape every schema host has: the
 * action's trigger context carries the form record's key, and the host has
 * nothing to resolve it with. Mounting it must therefore never reach the host
 * resolver, because the record comes off the component chain instead.
 */
#[AsLiveComponent(name: 'RecordBoundFormHost', template: '@ActionsFixtures/components/RecordBoundFormHost.html.twig')]
class RecordBoundFormHost implements InteractsWithActions, InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    public object $post;

    public function __construct()
    {
        $this->post = new class () {
            public int $id = 5;

            public string $title = 'Bound';
        };
    }

    public function schema(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->record($this->post)
            ->components([
                ActionSchemaField::make('title'),
                Actions::make([
                    Action::make('ping')->action(fn (): null => null),
                ]),
            ]);
    }
}

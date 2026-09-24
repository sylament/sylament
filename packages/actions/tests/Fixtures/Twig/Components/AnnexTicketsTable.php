<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Actions\Tests\Fixtures\Doctrine\AnnexManager;
use Sylament\Actions\Tests\Fixtures\Doctrine\Entity\Ticket;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table whose `query()` runs on {@see AnnexManager} rather than the
 * container's manager, over a single assigned string key: a create in its
 * header, an edit and a replicate on each row, each modal naming the key and
 * the title. The template renders the modals alone, since the actions suite
 * registers no tables bundle to render the table with.
 */
#[AsLiveComponent(name: 'AnnexTicketsTable', template: '@ActionsFixtures/components/AnnexTicketsTable.html.twig')]
class AnnexTicketsTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(private readonly AnnexManager $annex)
    {
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->annex->get()->createQueryBuilder()->select('ticket')->from(Ticket::class, 'ticket'))
            ->headerActions([
                CreateAction::make()->schema($this->getFields()),
            ])
            ->recordActions([
                EditAction::make()->schema($this->getFields()),
                ReplicateAction::make()->schema($this->getFields()),
            ]);
    }

    /**
     * @return list<ActionSchemaField>
     */
    private function getFields(): array
    {
        return [ActionSchemaField::make('code'), ActionSchemaField::make('title')];
    }
}

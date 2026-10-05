<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Fixtures\Twig\Components;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
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
 * {@see AnnexTicketsTable}'s query on the second entity manager, with one row
 * action: a replicate that opens no form, so its key checks read the action's
 * own reader rather than a mounted schema's. The copy leaves the identifier
 * unset, so `beforeReplicaSaved()` gives it the original's key, `T-1`, which
 * only the second manager's database holds.
 */
#[AsLiveComponent(name: 'AnnexBareReplicateTable', template: '@ActionsFixtures/components/AnnexBareReplicateTable.html.twig')]
class AnnexBareReplicateTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
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
            ->recordActions([
                ReplicateAction::make()
                    ->beforeReplicaSaved(static function (Ticket $replica): void {
                        $replica->setCode('T-1');
                    }),
            ]);
    }
}

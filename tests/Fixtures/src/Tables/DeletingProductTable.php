<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\DeleteBulkAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table titled by name whose rows and toolbar delete: the plain `delete` of each,
 * `keep` and `skip` replacing the row's delete with a `using()` that answers false or true
 * and deletes nothing, and `handBuilt`, a bulk delete built with `new` rather than by the
 * container, given the translator its texts need and no manager registry.
 */
#[AsLiveComponent(name: 'Tables:DeletingProductTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class DeletingProductTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HookLog $log,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
            ])
            ->defaultSort('name')
            ->recordActions([
                DeleteAction::make(),
                DeleteAction::make('keep')
                    ->failureNotificationTitle('Kept')
                    ->using(function (Product $record): bool {
                        $this->log->record("keep was handed {$record->name}");

                        return false;
                    }),
                DeleteAction::make('skip')
                    ->using(function (Product $record): bool {
                        $this->log->record("skip was handed {$record->name}");

                        return true;
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                $this->buildByHand(),
            ]);
    }

    private function buildByHand(): DeleteBulkAction
    {
        $action = new DeleteBulkAction()->name('handBuilt');
        $action->setTranslator($this->translator);

        return $action->configure();
    }
}

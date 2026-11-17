<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\BulkAction;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Notifications\Notification;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\Product;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A product table whose bulk actions read the selection and fail on part of it: `archive`
 * reports one selected record archived, `purge` none; both title their failure and give it
 * a body from the counts the failure hands its closures.
 */
#[AsLiveComponent(name: 'Tables:PartlyFailingBulkTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class PartlyFailingBulkTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('product')->from(Product::class, 'product'))
            ->columns([
                TextColumn::make('name'),
            ])
            ->toolbarActions([
                $this->partlyFailing('archive')->action(static function (BulkAction $action, array $records): void {
                    $action->reportBulkProcessingSuccessfulRecordsCount(1);
                }),
                $this->partlyFailing('purge')->action(static function (BulkAction $action, array $records): void {
                    $action->reportCompleteBulkProcessingFailure();
                }),
            ]);
    }

    private function partlyFailing(string $name): BulkAction
    {
        return BulkAction::make($name)
            ->failureNotificationTitle(static fn (int $failureCount, int $totalCount, bool $isAll): string => $isAll ? "None of {$totalCount} done" : "{$failureCount} of {$totalCount} not done")
            ->failureNotification(static fn (Notification $notification, int $successCount, int $failureCount, int $totalCount, bool $isAll): Notification => $notification->body(sprintf('%d done and %d not, of %d%s', $successCount, $failureCount, $totalCount, $isAll ? ', every one failed' : '')));
    }
}

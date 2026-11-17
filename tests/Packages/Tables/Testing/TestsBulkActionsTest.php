<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Tables\BulkProductTable;

it('selects records by object and by key for a bulk action to act on', function (): void {
    $this->seed($byRecord = new Product('Lamp'), $byKey = new Product('Desk'), $kept = new Product('Chair'));

    $this->createLiveComponent(BulkProductTable::class)
        ->selectTableRecords([$byRecord, $byKey->id])
        ->callAction(TestAction::make('delete')->table()->bulk())
        ->assertCanSeeTableRecords([$kept])
        ->assertCanNotSeeTableRecords([$byRecord, $byKey]);
});

<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\SelectionReadingProductTable;

it('hands its body the selected records, or their keys once told not to fetch them', function (string $action, string $read): void {
    $this->seed($lamp = new Product('Lamp'), $vase = new Product('Vase'));

    $this->createLiveComponent(SelectionReadingProductTable::class)
        ->selectTableRecords([$lamp, $vase])
        ->callAction(TestAction::make($action)->table()->bulk());

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([$read]);
})->with([
    'fetched' => ['archive', 'archive read Lamp, Vase'],
    'by key' => ['archiveByKey', 'archiveByKey read string, string'],
]);

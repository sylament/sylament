<?php

declare(strict_types=1);

use Sylament\Actions\Testing\TestAction;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\AlignedColumnsTable;
use Sylament\Tests\Fixtures\Tables\ClickableCellsLarderJarTable;

it('keys its columns by name, a second set replacing the first and what was visible of it', function (): void {
    $table = new AlignedColumnsTable()->getTable()->columns([TextColumn::make('name'), TextColumn::make('sku')]);
    $visibleBefore = array_keys($table->getVisibleColumns());

    $table->columns([TextColumn::make('note')]);

    expect($visibleBefore)->toBe(['name', 'sku'])
        ->and(array_keys($table->getColumns()))->toBe(['note'])
        ->and(array_keys($table->getVisibleColumns()))->toBe(['note']);
});

it('asks its columns whether they are visible on the first read only, until the answer is flushed', function (): void {
    $asked = 0;
    $table = new AlignedColumnsTable()->getTable()->columns([
        TextColumn::make('name')->visible(static function () use (&$asked): bool {
            $asked++;

            return true;
        }),
    ]);

    $table->getVisibleColumns();

    $askedByOneRead = $asked;
    $table->getVisibleColumns();
    $askedByTwoReads = $asked;
    $table->flushCachedVisibleColumns();
    $table->getVisibleColumns();

    expect($askedByOneRead)->toBeGreaterThan(0)
        ->and($askedByTwoReads)->toBe($askedByOneRead)
        ->and($asked)->toBe(2 * $askedByOneRead);
});

it("mounts a column's action by its name, on the record of the row", function (): void {
    $this->seed(new LarderJar('Apricot'), $bramble = new LarderJar('Bramble'));

    $this->createLiveComponent(ClickableCellsLarderJarTable::class)->callAction(TestAction::make('restack')->table($bramble));

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['restack Bramble']);
});

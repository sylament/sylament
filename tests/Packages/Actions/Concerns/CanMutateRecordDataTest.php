<?php

declare(strict_types=1);

use Sylament\Actions\EditAction;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Tables\EditableHeadlineTable;

it("fills the modal with what mutateRecordDataUsing() makes of the record's data", function (): void {
    $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

    $this->createLiveComponent(EditableHeadlineTable::class)
        ->mountAction(TestAction::make('editMutated')->table($sun))
        ->assertActionDataSet(['title' => 'Filled by the mutator']);

    expect($this->reload($sun))->toBeRecord(title: 'Sun');
});

it('refuses a mutator that answers something other than data keyed by field name, naming the setter', function (): void {
    $this->seed($sun = new CappedHeadline('SUN', 'Sun', slug: 'sun'));

    expect(fn () => $this->createLiveComponent(EditableHeadlineTable::class)->mountAction(TestAction::make('editListed')->table($sun)))
        ->toThrow(TypeError::class, EditAction::class . '::mutateRecordDataUsing() must return an array keyed by field name.');
});

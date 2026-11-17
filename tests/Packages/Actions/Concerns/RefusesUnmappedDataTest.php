<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;

it('refuses by name each path mutateDataUsing() adds that no component maps, before anything is written', function (): void {
    expect(fn () => $this->createLiveComponent(RecordActionsHost::class)->callAction('createUnmapped', data: ['code' => 'MOON', 'title' => 'Moon', 'slug' => 'moon']))
        ->toThrow(LogicException::class, 'The [createUnmapped] action added [body, meta.source] in mutateDataUsing(), but no component of its schema maps those paths, so the default write would drop them.');

    expect($this->entityManager()->find(CappedHeadline::class, 'MOON'))->toBeNull();
});

it('writes what mutateDataUsing() overwrites or a hidden component maps, and passes a key a client typed', function (): void {
    $this->createLiveComponent(RecordActionsHost::class)
        ->mountAction('createHooked')
        ->setActionData(['code' => 'MOON', 'title' => 'Moon', 'typed' => 'by the client'])
        ->callMountedAction()
        ->assertActionNotMounted();

    expect($this->reload(new CappedHeadline('MOON')))->toBeRecord(title: 'Hook', slug: 'hooked');
});

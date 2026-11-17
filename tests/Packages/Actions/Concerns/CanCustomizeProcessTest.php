<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Actions\RecordActionsHost;
use Sylament\Tests\Fixtures\Entity\CappedHeadline;
use Sylament\Tests\Fixtures\Schemas\HookLog;

it('replaces the default write with using(), handing it the data, the schema as schema and form, and the record class', function (): void {
    $this->createLiveComponent(RecordActionsHost::class)
        ->callAction('createUsing', data: ['code' => 'moon', 'title' => 'Moon', 'slug' => 'moon'])
        ->assertActionNotMounted()
        ->assertNotified('Created');

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['using() made a ' . CappedHeadline::class . ' from code, title, slug, body'])
        ->and($this->reload(new CappedHeadline('MOON')))->toBeRecord(title: 'By using()')
        ->and($this->entityManager()->find(CappedHeadline::class, 'moon'))->toBeNull();
});

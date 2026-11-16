<?php

declare(strict_types=1);

use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Schemas\WriterForm;

it('writes the field a path leads to through that field, so the host holds the value it stores', function (): void {
    $tester = $this->createLiveComponent(WriterForm::class)
        ->fillForm(['cast' => 'go'])
        ->assertFormSet(['subscribed' => true]);

    expect($tester->component()->data['subscribed'])->toBe(1);
});

it('writes the value a closure answers, and answers it', function (): void {
    $this->createLiveComponent(WriterForm::class)
        ->fillForm(['evaluated' => 'go'])
        ->assertFormSet(['target' => 'From a closure', 'echo' => 'From a closure']);
});

it('runs the update hooks of the field it writes only when asked, and never those of the component around it', function (): void {
    $tester = $this->createLiveComponent(WriterForm::class)
        ->fillForm(['quiet' => 'go'])
        ->assertFormSet(['target' => 'Quiet']);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe([]);

    $tester->fillForm(['loud' => 'go'])->assertFormSet(['box' => ['inner' => 'Loud']]);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['inner']);
});

it('writes a path no field owns on the host', function (): void {
    $this->createLiveComponent(WriterForm::class)
        ->fillForm(['stray' => 'go'])
        ->assertFormSet(['extra' => 'Written']);
});

it('writes a path inside its own component on the host as it is, unless told to search that component too', function (): void {
    $tester = $this->createLiveComponent(WriterForm::class)
        ->fillForm(['settings' => ['trigger' => 'go'], 'options' => ['trigger' => 'go']]);

    expect($tester->component()->data['settings']['flag'])->toBeFalse()
        ->and($tester->component()->data['options']['flag'])->toBe(0);
});

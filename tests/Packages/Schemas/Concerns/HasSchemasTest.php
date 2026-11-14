<?php

declare(strict_types=1);

use Sylament\Forms\Validation\RequiredValue;
use Sylament\Tests\Fixtures\Schemas\CodeForm;

it('keeps the constraint each error failed beside its message, which the re-rendered host still holds', function (): void {
    $host = $this->createLiveComponent(CodeForm::class)->call('save')->component();

    expect($host->getErrorConstraintsForSchemaStatePath('data.code'))->toBe([RequiredValue::class])
        ->and($host->getErrorsForSchemaStatePath('data.code'))->toBe(['This value should not be blank.']);
});

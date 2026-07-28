<?php

declare(strict_types=1);

namespace Sylament\Actions\Tests\Unit\Concerns;

use Sylament\Actions\Tests\Fixtures\Concerns\CanSubmitFormFixture;

it('defaults to not submitting any form', function (): void {
    $fixture = new CanSubmitFormFixture();

    expect($fixture->canSubmitForm())->toBeFalse()
        ->and($fixture->getFormToSubmit())->toBeNull()
        ->and($fixture->getFormId())->toBeNull();
});

it('marks the action as submitting a named form', function (): void {
    $fixture = new CanSubmitFormFixture()
        ->submit('editForm');

    expect($fixture->canSubmitForm())->toBeTrue()
        ->and($fixture->getFormToSubmit())->toBe('editForm');
});

it('treats submitting an empty string as not submitting a form', function (): void {
    $fixture = new CanSubmitFormFixture()
        ->submit('');

    expect($fixture->canSubmitForm())->toBeFalse()
        ->and($fixture->getFormToSubmit())->toBe('');
});

it('unsets the submitted form when passed null', function (): void {
    $fixture = new CanSubmitFormFixture()
        ->submit('editForm')
        ->submit(null);

    expect($fixture->canSubmitForm())->toBeFalse()
        ->and($fixture->getFormToSubmit())->toBeNull();
});

it('sets and reads the form id', function (): void {
    $fixture = new CanSubmitFormFixture()
        ->formId('my-form');

    expect($fixture->getFormId())->toBe('my-form');
});

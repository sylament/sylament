<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tests\Fixtures\Schemas\CodeForm;

it('fails asserting a form state the host does not hold', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => 'X1']);

    expect(fn () => $tester->assertFormSet(['code' => null]))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a component [code] has the expected state');
});

<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tests\Fixtures\Schemas\CodeForm;

it('fails asserting that a schema the host lacks exists', function (): void {
    expect(fn () => $this->createLiveComponent(CodeForm::class)->assertSchemaExists('settings'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a schema with the name [settings] exists');
});

it('fails asserting a schema state the host does not hold', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => 'X1']);

    expect(fn () => $tester->assertSchemaStateSet(['code' => null], 'form'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a component [code] has the expected state');
});

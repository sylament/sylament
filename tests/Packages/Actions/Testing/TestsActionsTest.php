<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Schemas\CodeForm;

it('fails asserting that an action exists when its last level is missing, though its parent resolves', function (): void {
    $actions = [TestAction::make('clear')->schemaComponent('code'), TestAction::make('nope')->schemaComponent('code', schema: 'form')];

    expect(fn () => $this->createLiveComponent(CodeForm::class)->assertActionExists($actions))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that an action with name [clear > nope] exists');
});

it('fails asserting that a hidden action is visible', function (): void {
    $tester = $this->createLiveComponent(ToolbarHost::class)->assertActionExists('purge');

    expect(fn () => $tester->assertActionVisible('purge'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that an action with name [purge] is visible');
});

it('refuses a table record on a host that is not a table', function (): void {
    expect(fn () => $this->createLiveComponent(CodeForm::class)->mountAction(TestAction::make('clear')->table(new stdClass())))
        ->toThrow(LogicException::class, 'is not a table host: it does not implement [Sylament\Tables\Contracts\InteractsWithTable]');
});

<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\MountedActionsHost;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Tables\ProductTable;

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

it('keeps a modal action mounted with its errors until its data passes', function (): void {
    $this->createLiveComponent(MountedActionsHost::class)
        ->callAction('rename')
        ->assertHasActionErrors(['name'])
        ->assertActionMounted('rename')
        ->setActionData(['name' => 'Lamp'])
        ->assertActionDataSet(['name' => 'Lamp'])
        ->assertHasNoActionErrors()
        ->callMountedAction()
        ->assertActionNotMounted();
});

it('keeps a halted action mounted', function (): void {
    $this->createLiveComponent(MountedActionsHost::class)
        ->callAction('hold')
        ->assertActionHalted('hold');
});

it('fails asserting that an action is mounted when none is', function (): void {
    expect(fn () => $this->createLiveComponent(MountedActionsHost::class)->assertActionMounted())
        ->toThrow(AssertionFailedError::class, 'Failed asserting that an action is mounted.');
});

it('fails asserting that an action is mounted when another one is', function (): void {
    $tester = $this->createLiveComponent(MountedActionsHost::class)
        ->mountAction('rename')
        ->assertActionMounted()
        ->assertActionMounted('rename');

    expect(fn () => $tester->assertActionMounted('hold'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the action [hold] is mounted.');
});

it('compares the mounted arguments only when the assertion names some', function (): void {
    $tester = $this->createLiveComponent(MountedActionsHost::class)
        ->mountAction('rename', ['tone' => 'loud'])
        ->assertActionMounted('rename')
        ->assertActionMounted(TestAction::make('rename')->arguments(['tone' => 'loud']))
        ->assertActionMounted(TestAction::make('rename')->arguments(fn (array $arguments): bool => $arguments === ['tone' => 'loud']));

    expect(fn () => $tester->assertActionMounted(TestAction::make('rename')->arguments(['tone' => 'soft'])))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the action [rename] is mounted with the expected arguments.')
        ->and(fn () => $tester->assertActionMounted(TestAction::make('rename')->arguments(fn (array $arguments): bool => $arguments === [])))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the mounted arguments for the action [rename] match the expected arguments.');
});

it('compares the mounted context with the one the assertion names', function (): void {
    $this->seed($mounted = new Product('Lamp'), $other = new Product('Vase'));

    $tester = $this->createLiveComponent(ProductTable::class)
        ->mountAction(TestAction::make('delete')->table($mounted))
        ->assertActionMounted(TestAction::make('delete')->table($mounted))
        ->assertActionNotMounted(TestAction::make('delete')->table($other));

    expect(fn () => $tester->assertActionMounted(TestAction::make('delete')->table($other)))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the action [delete] is mounted in the expected context.')
        ->and(fn () => $tester->assertActionNotMounted(TestAction::make('delete')->table($mounted)))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the action is not mounted.');
});

it('fails asserting that no action is mounted until the mounted one is unmounted', function (): void {
    $tester = $this->createLiveComponent(MountedActionsHost::class)->mountAction('rename');

    expect(fn () => $tester->assertActionNotMounted())
        ->toThrow(AssertionFailedError::class, 'Failed asserting that no action is mounted.')
        ->and($tester->unmountAction()->assertActionNotMounted())->toBe($tester);
});

it('fails asserting that the mounted action is not mounted', function (): void {
    $tester = $this->createLiveComponent(MountedActionsHost::class)
        ->mountAction('rename', ['tone' => 'loud'])
        ->assertActionNotMounted('hold')
        ->assertActionNotMounted(TestAction::make('rename')->arguments(['tone' => 'soft']))
        ->assertActionNotMounted(TestAction::make('rename')->arguments(fn (array $arguments): bool => $arguments === []));

    expect(fn () => $tester->assertActionNotMounted('rename'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that the action is not mounted.');
});

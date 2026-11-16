<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\EmptySchemaListForm;
use Sylament\Tests\Fixtures\Schemas\ReactiveSchemaForm;
use Sylament\Tests\Fixtures\Schemas\SchemalessLiveHost;

it('fails asserting that a schema the host lacks exists', function (): void {
    expect(fn () => $this->createLiveComponent(CodeForm::class)->assertSchemaExists('settings'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a schema with the name [settings] exists');
});

it('fails asserting a schema state the host does not hold', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->fillForm(['code' => 'X1']);

    expect(fn () => $tester->assertSchemaStateSet(['code' => null], 'form'))
        ->toThrow(AssertionFailedError::class, 'Failed asserting that a component [code] has the expected state');
});

describe('assertSchemaExists()', function (): void {
    it('passes for a schema the host builds', function (): void {
        $tester = $this->createLiveComponent(CodeForm::class);

        expect($tester->assertSchemaExists('form'))->toBe($tester);
    });

    it('fails naming the host for a host that holds no schemas', function (): void {
        expect(fn () => $this->createLiveComponent(SchemalessLiveHost::class)->assertSchemaExists('form'))
            ->toThrow(AssertionFailedError::class, 'Failed asserting that a schema with the name [form] exists on the [' . SchemalessLiveHost::class . '] component.');
    });
});

describe('assertSchemaStateSet()', function (): void {
    it("passes for each key's component state, a hidden component's included, and for the host's value at a path no component names", function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class)->fillForm(['title' => 'First']);

        expect($tester->assertSchemaStateSet(['title' => 'First', 'secret' => 'kept', 'typo' => null]))->toBe($tester);
    });

    it("compares the array a closure returns from the schema's raw state", function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class)->fillForm(['title' => 'First']);

        expect(fn () => $tester->assertSchemaStateSet(function (array $state): array {
            expect($state)->toMatchArray(['title' => 'First', 'secret' => 'kept']);

            return ['title' => 'Other'];
        }))->toThrow(AssertionFailedError::class, 'Failed asserting that a component [title] has the expected state');
    });

    it('fails for a path no component names whose value on the host differs', function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class);

        expect(fn () => $tester->assertSchemaStateSet(['typo' => 'Anything']))->toThrow(AssertionFailedError::class);
    });

    it('fails naming a hidden component whose state differs', function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class);

        expect(fn () => $tester->assertSchemaStateSet(['secret' => 'changed']))
            ->toThrow(AssertionFailedError::class, 'Failed asserting that a component [secret] has the expected state');
    });
});

describe('default schema', function (): void {
    it("tests the mounted action's schema while an action is mounted", function (): void {
        $tester = $this->createLiveComponent(ReactiveSchemaForm::class)
            ->mountAction('rename')
            ->fillForm(['name' => 'Renamed'], 'mountedActionSchema0');

        expect($tester->assertSchemaStateSet(['name' => 'Renamed']))->toBe($tester);
    });

    it('throws for a host whose schemas() declares none', function (): void {
        expect(fn () => $this->createLiveComponent(EmptySchemaListForm::class)->assertSchemaStateSet([]))
            ->toThrow(LogicException::class, 'The [' . EmptySchemaListForm::class . '] component declares no schema to test.');
    });
});

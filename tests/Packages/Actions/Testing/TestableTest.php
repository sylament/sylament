<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Tables\ProductTable;

it('rethrows what a host action throws, never an error page, while a failed validation answers its re-render', function (): void {
    expect($this->createLiveComponent(CodeForm::class)->call('save')->response()->getStatusCode())->toBe(422)
        ->and(fn () => $this->createLiveComponent(ToolbarHost::class)->call('fail'))->toThrow(RuntimeException::class, 'The toolbar failed.');
});

it('asserts the redirect an action answered and its toast, and reads nothing else of the component after it', function (): void {
    $this->seed($product = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)
        ->assertNoRedirect()
        ->callAction(TestAction::make('open')->table($product))
        ->assertRedirect("/products/{$product->id}")
        ->assertNotified('Opened');

    expect(fn () => $tester->component())
        ->toThrow(LogicException::class, "A live component action has redirected to [/products/{$product->id}] and you can no longer access the component.");
});

it('fails asserting a redirect the component did not perform', function (): void {
    expect(fn () => $this->createLiveComponent(CodeForm::class)->assertRedirect())
        ->toThrow(AssertionFailedError::class, 'Component did not perform a redirect.');
});

it('fails asserting a redirect to another location', function (): void {
    $this->seed($product = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)->callAction(TestAction::make('open')->table($product));

    expect(fn () => $tester->assertRedirect('/products'))->toThrow(AssertionFailedError::class);
});

it('fails asserting no redirect after the component redirected', function (): void {
    $this->seed($product = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)->callAction(TestAction::make('open')->table($product));

    expect(fn () => $tester->assertNoRedirect())
        ->toThrow(AssertionFailedError::class, "Component performed a redirect to [/products/{$product->id}].");
});

<?php

declare(strict_types=1);

use PHPUnit\Framework\AssertionFailedError;
use Sylament\Actions\Testing\TestAction;
use Sylament\Tests\Fixtures\Actions\HydrationRefusingHost;
use Sylament\Tests\Fixtures\Actions\ListeningHost;
use Sylament\Tests\Fixtures\Actions\ToolbarHost;
use Sylament\Tests\Fixtures\Entity\Product;
use Sylament\Tests\Fixtures\Forms\Selectors as FormsSelectors;
use Sylament\Tests\Fixtures\Notifications\NotifyingHost;
use Sylament\Tests\Fixtures\Schemas\CodeForm;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Fixtures\Tables\ProductTable;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

it('sends each call as a request of its own, and reads the host, the response and the render that request answered', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class);

    expect($tester->set('data.code', 'A1'))->toBe($tester)
        ->and($tester->component()->data)->toBe(['code' => 'A1'])
        ->and($tester->refresh()->call('save'))->toBe($tester)
        ->and($tester->response()->getStatusCode())->toBe(200)
        ->and($tester->render()->crawler())->toContainElement('input[value="A1"]');
});

it("submits the form's values with the live action it names, answering a failed validation with its re-render", function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->submitForm(['data' => ['code' => 'A1']], 'save');

    expect($tester->response()->getStatusCode())->toBe(200)
        ->and($tester->assertFormSet(['code' => 'A1']))->toBe($tester)
        ->and($this->createLiveComponent(CodeForm::class)->submitForm(['data' => ['code' => '']], 'save')->response()->getStatusCode())->toBe(422);
});

it('delivers an event to the listener the host declares for it', function (): void {
    $this->createLiveComponent(ListeningHost::class)->emit('restocked', ['sku' => 'A1']);

    expect($this::getContainer()->get(HookLog::class)->hooks)->toBe(['restocked A1']);
});

it('rethrows what a host action throws, never an error page, while a failed validation answers its re-render', function (): void {
    $tester = $this->createLiveComponent(CodeForm::class)->call('save');

    expect($tester->response()->getStatusCode())->toBe(422)
        ->and($tester->render()->crawler())->toContainElement(FormsSelectors::ERROR_MESSAGE, 'This value should not be blank.')
        ->and(fn () => $this->createLiveComponent(ToolbarHost::class)->call('fail'))->toThrow(RuntimeException::class, 'The toolbar failed.');
});

it('rethrows a 422 raised before UX mounted the component, since only its re-render answers a failed validation', function (): void {
    expect(fn () => $this->createLiveComponent(HydrationRefusingHost::class, ['refusing' => true])->response())
        ->toThrow(UnprocessableEntityHttpException::class, 'Refused while hydrating.');
});

it('asserts the redirect an action answered and its toast, and reads nothing else of the component after it', function (): void {
    $this->seed($product = new Product('Lamp'));

    $tester = $this->createLiveComponent(ProductTable::class)
        ->assertNoRedirect()
        ->callAction(TestAction::make('open')->table($product))
        ->assertRedirect("/products/{$product->id}")
        ->assertRedirect("http://localhost/products/{$product->id}")
        ->assertNotified('Opened');
    $message = "A live component action has redirected to [/products/{$product->id}] and you can no longer access the component.";

    expect(fn () => $tester->component())->toThrow(LogicException::class, $message)
        ->and(fn () => $tester->render())->toThrow(LogicException::class, $message)
        ->and(fn () => $tester->call('mountAction', ['name' => 'open']))->toThrow(LogicException::class, $message);
});

it('counts a notification the host sent while answering its mount request', function (): void {
    $tester = $this->createLiveComponent(NotifyingHost::class, ['notifiesWhileRendering' => true]);

    expect($tester->assertNotified('Rendered'))->toBe($tester);
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

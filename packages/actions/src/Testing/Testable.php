<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use LogicException;
use PHPUnit\Framework\Assert;
use Sylament\Forms\Testing\TestsForms;
use Sylament\Notifications\Notification;
use Sylament\Notifications\Testing\NotificationRecorder;
use Sylament\Notifications\Testing\TestsNotifications;
use Sylament\Schemas\Testing\TestsSchemas;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseHeaderLocationSame;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Symfony\UX\TwigComponent\MountedComponent;
use Symfony\UX\TwigComponent\Test\RenderedComponent;
use Throwable;

/**
 * Drives one live component through UX's `TestLiveComponent` with the kernel catching
 * exceptions, as a browser's request would: a failed validation answers its 422
 * re-render, and any other failure rethrows the exception the host threw. After a
 * redirect, or a failure it rethrew, only the response and the notifications its requests
 * sent can be read.
 */
class Testable
{
    use TestsActions;
    use TestsForms;
    use TestsSchemas;

    private const string LIVE_RESPONSE_CONTENT_TYPE = 'application/vnd.live-component+html';

    private bool $performedInitialRequest = false;

    /**
     * @var list<array<string, mixed>>
     */
    private array $sentNotifications = [];

    public function __construct(
        private readonly TestLiveComponent $testLiveComponent,
        private readonly KernelBrowser $client,
    ) {
        $client->catchExceptions(true);
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, UploadedFile|array<UploadedFile>> $files
     */
    public function call(string $action, array $arguments = [], array $files = []): static
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();
        $this->testLiveComponent->call($action, $arguments, $files);
        $this->afterRequest();

        return $this;
    }

    public function set(string $prop, mixed $value): static
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();
        $this->testLiveComponent->set($prop, $value);
        $this->afterRequest();

        return $this;
    }

    public function refresh(): static
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();
        $this->testLiveComponent->refresh();
        $this->afterRequest();

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function emit(string $event, array $arguments = []): static
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();
        $this->testLiveComponent->emit($event, $arguments);
        $this->afterRequest();

        return $this;
    }

    /**
     * @param array<string, mixed> $formValues
     */
    public function submitForm(array $formValues, ?string $action = null): static
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();
        $this->testLiveComponent->submitForm($formValues, $action);
        $this->afterRequest();

        return $this;
    }

    public function setRouteLocale(string $locale): static
    {
        $this->testLiveComponent->setRouteLocale($locale);
        $this->performedInitialRequest = false;

        return $this;
    }

    public function render(): RenderedComponent
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();

        return $this->testLiveComponent->render();
    }

    public function component(): object
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();

        return $this->testLiveComponent->component();
    }

    public function response(): Response
    {
        $this->performInitialRequest();

        return $this->testLiveComponent->response();
    }

    public function assertRedirect(?string $uri = null): static
    {
        $response = $this->response();

        Assert::assertTrue($response->isRedirection(), 'Component did not perform a redirect.');

        if ($uri !== null) {
            Assert::assertThat($response, new ResponseHeaderLocationSame($this->client->getRequest(), $uri));
        }

        return $this;
    }

    public function assertNoRedirect(): static
    {
        $response = $this->response();

        Assert::assertFalse($response->isRedirection(), sprintf('Component performed a redirect to [%s].', $response->headers->get('Location')));

        return $this;
    }

    public function assertNotified(Notification|string|null $notification = null): static
    {
        $sentNotifications = $this->takeSentNotifications();

        TestsNotifications::assertNotified($sentNotifications, $notification);

        return $this;
    }

    public function assertNotNotified(Notification|string|null $notification = null): static
    {
        $sentNotifications = $this->takeSentNotifications();

        TestsNotifications::assertNotNotified($sentNotifications, $notification);

        return $this;
    }

    /**
     * The component as it answered the last request. {@see component()} re-hydrates it
     * from the response, which re-runs only what hydration runs, so it no longer holds
     * what that request alone produced, such as a record's refusal in the error bag the
     * 422 re-render showed; UX keeps the instance it answered with on the request.
     */
    protected function getRespondingComponent(): object
    {
        $this->performInitialRequest();
        $this->failWithoutComponent();

        $mountedComponent = $this->client->getRequest()->attributes->get('_mounted_component');

        if (!$mountedComponent instanceof MountedComponent) {
            throw new LogicException('The last request mounted no live component to read.');
        }

        return $mountedComponent->getComponent();
    }

    /**
     * UX sends the mount request on first use, so it is made here, before anything
     * reads its response or sends the next request from its props.
     */
    private function performInitialRequest(): void
    {
        if ($this->performedInitialRequest) {
            return;
        }

        $this->performedInitialRequest = true;
        $this->testLiveComponent->response();
        $this->afterRequest();
    }

    private function afterRequest(): void
    {
        $this->collectSentNotifications();
        $this->rethrowCaughtException();
    }

    /**
     * The recorder answers for the request its container served, so it is read after each
     * one; without sylament/notifications or its bundle there is nothing to collect.
     */
    private function collectSentNotifications(): void
    {
        if (!class_exists(NotificationRecorder::class)) {
            return;
        }

        $container = $this->client->getContainer();

        if (!$container->has(NotificationRecorder::class)) {
            return;
        }

        $recorder = $container->get(NotificationRecorder::class);

        if (!$recorder instanceof NotificationRecorder) {
            throw new LogicException(sprintf('The [%s] service is not the notifications recorder.', NotificationRecorder::class));
        }

        array_push($this->sentNotifications, ...$recorder->sent());
    }

    /**
     * Call it before naming `TestsNotifications`: PHP resolves a static call's class before
     * evaluating its arguments, so the package guard here would otherwise never run.
     *
     * @return list<array<string, mixed>>
     */
    private function takeSentNotifications(): array
    {
        if (!class_exists(TestsNotifications::class)) {
            throw new LogicException('assertNotified() and assertNotNotified() need sylament/notifications, whose recorder keeps the notifications a request sends.');
        }

        $this->performInitialRequest();

        if (!$this->client->getContainer()->has(NotificationRecorder::class)) {
            throw new LogicException(sprintf('The kernel has no [%s]: SylamentNotificationsBundle registers it when framework.test is on.', NotificationRecorder::class));
        }

        $sentNotifications = $this->sentNotifications;
        $this->sentNotifications = [];

        return $sentNotifications;
    }

    /**
     * A redirect and a failure, rethrown or answered without an exception, both answer
     * without the component's props, which UX's tester would report as a redirect either
     * way; only a success and UX's 422 re-render carry them.
     */
    private function failWithoutComponent(): void
    {
        $response = $this->client->getResponse();

        if ($response->isRedirection()) {
            throw new LogicException(sprintf('A live component action has redirected to [%s] and you can no longer access the component.', $response->headers->get('Location')));
        }

        if ($response->isSuccessful() || str_starts_with((string) $response->headers->get('Content-Type'), self::LIVE_RESPONSE_CONTENT_TYPE)) {
            return;
        }

        throw new LogicException(sprintf('The live component answered [%d] to the last request, which failed, so no component is left to read or drive.', $response->getStatusCode()));
    }

    private function rethrowCaughtException(): void
    {
        $container = $this->client->getContainer();

        if (!$container->has(ExceptionRecorder::class)) {
            throw new LogicException(sprintf('The kernel has no [%s]: SylamentActionsBundle registers it when framework.test is on.', ExceptionRecorder::class));
        }

        $recorder = $container->get(ExceptionRecorder::class);

        if (!$recorder instanceof ExceptionRecorder) {
            throw new LogicException(sprintf('The [%s] service is not the exception recorder.', ExceptionRecorder::class));
        }

        $throwable = $recorder->takeThrowable();
        $response = $this->client->getResponse();

        if ($response->isSuccessful() || $response->isRedirection() || $this->isLiveValidationResponse($response, $throwable)) {
            return;
        }

        throw $throwable ?? new LogicException(sprintf('The live component answered [%d] without an exception to rethrow.', $response->getStatusCode()));
    }

    /**
     * UX re-renders the component for an `UnprocessableEntityHttpException` only once it is
     * mounted; one thrown earlier reaches the framework's error page, also a 422.
     */
    private function isLiveValidationResponse(Response $response, ?Throwable $throwable): bool
    {
        return ($response->getStatusCode() === Response::HTTP_UNPROCESSABLE_ENTITY)
            && ($throwable instanceof UnprocessableEntityHttpException)
            && str_starts_with((string) $response->headers->get('Content-Type'), self::LIVE_RESPONSE_CONTENT_TYPE);
    }
}

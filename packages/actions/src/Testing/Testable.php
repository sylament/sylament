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
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Symfony\UX\TwigComponent\Test\RenderedComponent;
use Throwable;

/**
 * Drives one live component through UX's `TestLiveComponent` with the kernel catching
 * exceptions, as a browser's request would: a failed validation answers its 422
 * re-render, and any other failure rethrows the exception the host threw. After a
 * redirect only the response and the notifications its requests sent can be read.
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
        $this->failIfRedirected();
        $this->testLiveComponent->call($action, $arguments, $files);
        $this->afterRequest();

        return $this;
    }

    public function set(string $prop, mixed $value): static
    {
        $this->performInitialRequest();
        $this->failIfRedirected();
        $this->testLiveComponent->set($prop, $value);
        $this->afterRequest();

        return $this;
    }

    public function refresh(): static
    {
        $this->performInitialRequest();
        $this->failIfRedirected();
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
        $this->failIfRedirected();
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
        $this->failIfRedirected();
        $this->testLiveComponent->submitForm($formValues, $action);
        $this->afterRequest();

        return $this;
    }

    /**
     * @param UserInterface $user
     */
    public function actingAs(object $user, string $firewallContext = 'main'): static
    {
        $this->testLiveComponent->actingAs($user, $firewallContext);

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
        $this->failIfRedirected();

        return $this->testLiveComponent->render();
    }

    public function component(): object
    {
        $this->performInitialRequest();
        $this->failIfRedirected();

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
        TestsNotifications::assertNotified($this->takeSentNotifications(), $notification);

        return $this;
    }

    public function assertNotNotified(Notification|string|null $notification = null): static
    {
        TestsNotifications::assertNotNotified($this->takeSentNotifications(), $notification);

        return $this;
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

    private function failIfRedirected(): void
    {
        $response = $this->client->getResponse();

        if (!$response->isRedirection()) {
            return;
        }

        throw new LogicException(sprintf('A live component action has redirected to [%s] and you can no longer access the component.', $response->headers->get('Location')));
    }

    private function rethrowCaughtException(): void
    {
        $recorder = $this->client->getContainer()->get(ExceptionRecorder::class);

        if (!$recorder instanceof ExceptionRecorder) {
            throw new LogicException(sprintf('The kernel has no [%s]: SylamentActionsBundle registers it when framework.test is on.', ExceptionRecorder::class));
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

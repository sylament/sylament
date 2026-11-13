<?php

declare(strict_types=1);

namespace Sylament\Actions\Testing;

use LogicException;
use Sylament\Forms\Testing\TestsForms;
use Sylament\Schemas\Testing\TestsSchemas;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\LiveComponent\Test\TestLiveComponent;
use Symfony\UX\TwigComponent\Test\RenderedComponent;
use Throwable;

/**
 * Drives one live component through UX's `TestLiveComponent` with the kernel catching
 * exceptions, as a browser's request would: a failed validation answers its 422
 * re-render, and any other failure rethrows the exception the host threw.
 */
class Testable
{
    use TestsActions;
    use TestsForms;
    use TestsSchemas;

    private const string LIVE_RESPONSE_CONTENT_TYPE = 'application/vnd.live-component+html';

    private bool $performedInitialRequest = false;

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
        $this->testLiveComponent->call($action, $arguments, $files);
        $this->rethrowCaughtException();

        return $this;
    }

    public function set(string $prop, mixed $value): static
    {
        $this->performInitialRequest();
        $this->testLiveComponent->set($prop, $value);
        $this->rethrowCaughtException();

        return $this;
    }

    public function refresh(): static
    {
        $this->performInitialRequest();
        $this->testLiveComponent->refresh();
        $this->rethrowCaughtException();

        return $this;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function emit(string $event, array $arguments = []): static
    {
        $this->performInitialRequest();
        $this->testLiveComponent->emit($event, $arguments);
        $this->rethrowCaughtException();

        return $this;
    }

    /**
     * @param array<string, mixed> $formValues
     */
    public function submitForm(array $formValues, ?string $action = null): static
    {
        $this->performInitialRequest();
        $this->testLiveComponent->submitForm($formValues, $action);
        $this->rethrowCaughtException();

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

        return $this->testLiveComponent->render();
    }

    public function component(): object
    {
        $this->performInitialRequest();

        return $this->testLiveComponent->component();
    }

    public function response(): Response
    {
        $this->performInitialRequest();

        return $this->testLiveComponent->response();
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
        $this->rethrowCaughtException();
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

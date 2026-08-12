<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Notifications\Notification;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Markup;

use function Sylament\Support\blank;

abstract class CreateRecord extends Page implements InteractsWithSchemas
{
    use HasSchemas;

    protected ?string $template = '@SylamentPanels/partials/CreateRecord/index.html.twig';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The created record, set by `create()` — a plain property rather than a
     * `LiveProp`, because it exists only between the write and the redirect
     * that ends the component's life.
     */
    protected ?object $record = null;

    private ?EntityManagerInterface $entityManager = null;

    #[Required]
    public function setRecordPersistence(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function mount(): void
    {
        $this->getSchema('form')->fill();
    }

    public function form(Schema $schema): Schema
    {
        $resource = $this->getResourceInstance();

        return $resource->form(
            $schema
                ->statePath('data')
                ->record($resource->getRecordClass())
                ->operation(Operation::Create->value),
        );
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [
            'form',
        ];
    }

    #[LiveAction]
    public function create(): ?RedirectResponse
    {
        $record = $this->getSchema('form')->getData();

        $this->record = $this->handleRecordCreation($record);

        $this->getCreatedNotification()?->send();

        return new RedirectResponse($this->getRedirectUrl());
    }

    public function getRecord(): ?object
    {
        return $this->record;
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? sprintf('Create %s', $this->getResourceInstance()->getTitleCaseRecordLabel());
    }

    protected function handleRecordCreation(object $record): object
    {
        $entityManager = $this->entityManager
            ?? throw new LogicException(sprintf('The resource page [%s] was built outside the container, so it holds no entity manager; register it as an autowired service.', static::class));

        $entityManager->persist($record);
        $entityManager->flush();

        return $record;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $title = $this->getCreatedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Created';
    }

    protected function getRedirectUrl(): string
    {
        $resource = $this->getResourceInstance();

        if ($resource->hasPage('edit')) {
            return $resource->getUrl('edit', ['record' => $this->getRecord()]);
        }

        return $resource->getIndexUrl();
    }
}

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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Markup;

use function Sylament\Support\blank;

abstract class EditRecord extends Page implements InteractsWithSchemas
{
    use HasSchemas;

    protected ?string $template = '@SylamentPanels/partials/EditRecord/index.html.twig';

    /**
     * The record's route key, never the record itself — the wire carries a
     * scalar and `getRecord()` resolves it. The type is a single nullable
     * string rather than Filament's `int|string`, because
     * `LiveComponentMetadataFactory` throws on any union-typed `LiveProp`;
     * `mount()` takes the union instead.
     */
    #[LiveProp(dehydrateWith: 'dehydrateRecord')]
    public ?string $record = null;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    protected ?object $resolvedRecord = null;

    private ?EntityManagerInterface $entityManager = null;

    #[Required]
    public function setRecordPersistence(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function mount(int|string $record): void
    {
        $this->record = (string) $record;

        $this->getSchema('form')->fill($this->getRecord());
    }

    /**
     * The wire value is the route key the property holds, not the entity
     * `PropertyAccessor` reads out of the same-named `getRecord()`.
     */
    public function dehydrateRecord(mixed $value): ?string
    {
        return $this->record;
    }

    public function getRecord(): object
    {
        if ($this->resolvedRecord !== null) {
            return $this->resolvedRecord;
        }

        $key = $this->record;

        if (blank($key)) {
            throw new NotFoundHttpException(sprintf('The page [%s] carries no record key to resolve.', static::class));
        }

        $record = $this->getResourceInstance()->resolveRecordRouteBinding($key);

        if ($record === null) {
            throw new NotFoundHttpException(sprintf('No record matching [%s] exists for the resource [%s].', $key, static::getResource()));
        }

        return $this->resolvedRecord = $record;
    }

    public function form(Schema $schema): Schema
    {
        $resource = $this->getResourceInstance();

        return $resource->form(
            $schema
                ->statePath('data')
                ->record($this->getRecord())
                ->operation(Operation::Edit->value),
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
    public function save(): ?RedirectResponse
    {
        $record = $this->getSchema('form')->getData();

        $this->handleRecordUpdate($record);

        $this->getSavedNotification()?->send();

        $redirectUrl = $this->getRedirectUrl();

        return $redirectUrl === null ? null : new RedirectResponse($redirectUrl);
    }

    /**
     * Pre-mount safe: `PageController` reads the document title off an
     * un-mounted page, where the record key is still null.
     */
    public function getRecordTitle(): string
    {
        $resource = $this->getResourceInstance();

        if ($this->record === null) {
            return $resource->getTitleCaseRecordLabel();
        }

        return $resource->hasRecordTitle()
            ? $resource->getRecordTitle($this->getRecord())
            : $resource->getTitleCaseRecordLabel();
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? sprintf('Edit %s', $this->getRecordTitle());
    }

    protected function handleRecordUpdate(object $record): object
    {
        $entityManager = $this->entityManager
            ?? throw new LogicException(sprintf('The resource page [%s] was built outside the container, so it holds no entity manager; register it as an autowired service.', static::class));

        $entityManager->persist($record);
        $entityManager->flush();

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        $title = $this->getSavedNotificationTitle();

        if (blank($title)) {
            return null;
        }

        return Notification::make()
            ->success()
            ->title($title);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Saved';
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }
}

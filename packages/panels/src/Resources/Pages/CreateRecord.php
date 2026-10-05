<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Notifications\Notification;
use Sylament\Panels\Pages\Concerns\HasUnsavedDataChangesAlert;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Sylament\Support\Exceptions\Halt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Markup;

use function Sylament\Support\blank;

abstract class CreateRecord extends Page
{
    use HasUnsavedDataChangesAlert;

    protected ?string $template = '@SylamentPanels/partials/Page/form.html.twig';

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

    /**
     * @throws LogicException when the page was built outside the container
     */
    private function requireEntityManager(): EntityManagerInterface
    {
        return $this->entityManager
            ?? throw new LogicException(sprintf('The resource page [%s] was built outside the container, so it holds no entity manager; register it as an autowired service.', static::class));
    }

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(array $parentRecords = []): void
    {
        $this->parentRecords = $parentRecords;

        $this->fillForm();
        $this->rememberData();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->getSchema('form')->fill();

        $this->callHook('afterFill');
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
        $schema = $this->getSchema('form');

        try {
            $this->callHook('beforeValidate');

            $data = $schema->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($this->mapRecordForCreation($schema, $data));

            $this->callHook('afterCreate');
        } catch (Halt) {
            return null;
        }

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        return new RedirectResponse($this->getRedirectUrl());
    }

    public function getSubmitFormLiveComponentMethodName(): string
    {
        return 'create';
    }

    /**
     * @return list<string>
     */
    public function getPageClasses(): array
    {
        return [
            'sy-pa-resource-create-record-page',
            'sy-pa-resource-' . str_replace('/', '-', $this->getResourceInstance()->getSlug()),
        ];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Create')
            ->submit($this->getSubmitFormLiveComponentMethodName());
    }

    /**
     * Filament's cancel is a client-side history-back the port has no seam for,
     * so it links to the listing instead — form-actions.md.
     */
    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->url($this->getResourceInstance()->getIndexUrl([...$this->parentRecords]));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * The new record the form is mapped onto. The default builds the resource's
     * record class with no arguments; override it to build a record whose
     * constructor takes them, from the validated and mutated form data.
     *
     * @param array<string, mixed> $data
     *
     * @throws LogicException when the default meets a record class whose constructor requires arguments
     */
    protected function makeRecord(array $data): object
    {
        return $this->getSchema('form')->getPropertyAccessor()->constructRecord(
            $this->getResourceInstance()->getRecordClass(),
            sprintf('a new record on the resource page [%s]', static::class),
            'override makeRecord() to build it from the form data.',
        );
    }

    /**
     * @return object|array<string, mixed>|string|null
     */
    protected function getMountedActionSchemaRecord(): object|array|string|null
    {
        return $this->getResourceInstance()->getRecordClass();
    }

    public function getRecord(): ?object
    {
        return $this->record;
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? sprintf('Create %s', $this->getResourceInstance()->getTitleCaseRecordLabel());
    }

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? 'Create';
    }

    /**
     * The state mapped onto the record {@see makeRecord()} builds, called once.
     * One created under a parent joins it before the mapping, so the record
     * check that ends the mapping sees an identifier field the parent fills; the
     * form is bound to the made record for the mapping alone, and that check
     * reads the form's own reader, rebound to the manager the page writes
     * through.
     *
     * @param array<string, mixed> $data
     *
     * @throws LogicException when makeRecord() answers an object that is no instance of the resource's record class
     */
    private function mapRecordForCreation(Schema $schema, array $data): object
    {
        $resource = $this->getResourceInstance();
        $recordClass = $resource->getRecordClass();
        $record = $this->makeRecord($data);

        if (! $record instanceof $recordClass) {
            throw new LogicException(sprintf(
                'The resource page [%s] built a [%s] in makeRecord(), but its resource [%s] creates [%s] records, so the form was not mapped onto it. Answer a new [%s] from makeRecord().',
                static::class,
                $record::class,
                $resource::class,
                $recordClass,
                $recordClass,
            ));
        }

        $parentRecord = $this->getParentRecord();

        if ($parentRecord !== null) {
            $resource->attachRecordToParent($record, $parentRecord);
        }

        $schema->setAssociationReader($schema->getAssociationReader()->withEntityManager($this->requireEntityManager()));

        $binding = $schema->getRecord() ?? $schema->getRecordClass();

        try {
            return $schema->record($record)->getData($data);
        } finally {
            $schema->record($binding);
        }
    }

    /**
     * A record created under a parent joins it in the same flush — an
     * association the mapping refuses to leave empty would otherwise fail the
     * write rather than the form. A record whose assigned key is still blank
     * once `persist()` has run every prePersist listener is refused by name
     * before the flush and taken back out of the unit of work; the form's own
     * reader, rebound to the page's manager, answers that read.
     *
     * @throws LogicException when the persisted record's assigned key is still blank
     */
    protected function handleRecordCreation(object $record): object
    {
        $entityManager = $this->requireEntityManager();

        $parentRecord = $this->getParentRecord();

        if ($parentRecord !== null) {
            $this->getResourceInstance()->attachRecordToParent($record, $parentRecord);
        }

        $entityManager->persist($record);

        if ($this->getSchema('form')->getAssociationReader()->withEntityManager($entityManager)->hasBlankAssignedIdentifier($record)) {
            $entityManager->detach($record);

            throw new LogicException(sprintf(
                'The resource page [%s] built a [%s] whose identifier is still blank after persist(), so it was not saved. Give every identifier field a form field — a Hidden one for a value the user should not type — or fill it in a prePersist listener, in makeRecord(), or in handleRecordCreation() before calling the parent.',
                static::class,
                $record::class,
            ));
        }

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
            return $resource->getUrl('edit', [...$this->parentRecords, 'record' => $this->getRecord()]);
        }

        return $resource->getIndexUrl([...$this->parentRecords]);
    }
}

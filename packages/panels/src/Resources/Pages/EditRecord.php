<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\Enums\ActionStatus;
use Sylament\Actions\ViewAction;
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

        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $schema = $this->getSchema('form');
        $schema->fill($this->mutateFormDataBeforeFill($schema->getStateFromRecord($this->getRecord())));

        $this->callHook('afterFill');
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
        $schema = $this->getSchema('form');

        $this->callHook('beforeValidate');

        // Filament's order: `beforeSave` fires inside the state read, before the
        // mutator below — normalising it would be a silent divergence.
        $data = $schema->getState(afterValidate: function (): void {
            $this->callHook('afterValidate');
            $this->callHook('beforeSave');
        });

        $data = $this->mutateFormDataBeforeSave($data);

        $this->handleRecordUpdate($schema->getData($data));

        $this->callHook('afterSave');

        $this->getSavedNotification()?->send();

        $redirectUrl = $this->getRedirectUrl();

        return $redirectUrl === null ? null : new RedirectResponse($redirectUrl);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    /**
     * A create modal on a record page is anonymous — the page's record would
     * otherwise open it as an edit of that record.
     *
     * @return object|array<string, mixed>|null
     */
    public function getDefaultActionRecord(Action $action): object|array|null
    {
        if ($action instanceof CreateAction || $this->record === null) {
            return null;
        }

        return $this->getRecord();
    }

    public function getDefaultActionRecordTitle(Action $action): ?string
    {
        return $this->getRecordTitle();
    }

    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string
    {
        return $action instanceof DeleteAction ? $this->getResourceInstance()->getIndexUrl() : null;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        $resource = $this->getResourceInstance();

        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $resource->form($schema->hasCustomColumns() ? $schema : $schema->columns(2)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $resource->infolist($resource->form($schema->hasCustomColumns() ? $schema : $schema->columns(2))),
            default => null,
        };
    }

    /**
     * Answers for the top frame, exactly as Filament's frame-agnostic hook
     * does. Reading the mounted action here is safe only because the seed's
     * record closure is lazy — an eager read during resolution would re-enter
     * the action cache.
     *
     * @return object|array<string, mixed>|string|null
     */
    protected function getMountedActionSchemaRecord(): object|array|string|null
    {
        if ($this->getMountedAction() instanceof CreateAction || $this->record === null) {
            return $this->getResourceInstance()->getRecordClass();
        }

        return $this->getRecord();
    }

    /**
     * The modal renders a second component tree over the same record, so the
     * page form is refilled from it rather than left showing pre-modal values.
     * The refill goes through `fillForm()`, so a page that mutates or hooks on
     * fill sees the same treatment it got at mount.
     */
    protected function afterActionCalled(Action $action): void
    {
        if (! $action instanceof EditAction || $action->getStatus() !== ActionStatus::Success || $this->record === null) {
            return;
        }

        if ($action->getRecord() === $this->getRecord()) {
            $this->fillForm();
        }
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

    public function getBreadcrumb(): string
    {
        return static::$breadcrumb ?? 'Edit';
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

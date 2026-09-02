<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Actions\CreateAction;
use Sylament\Actions\DeleteAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ViewAction;
use Sylament\Notifications\Notification;
use Sylament\Panels\Pages\Concerns\HasUnsavedDataChangesAlert;
use Sylament\Panels\Resources\Pages\Concerns\HasRelationManagers;
use Sylament\Panels\Resources\Pages\Concerns\InteractsWithRecord;
use Sylament\Schemas\Components\EmbeddedSchema;
use Sylament\Schemas\Schema;
use Sylament\Support\Enums\Operation;
use Sylament\Support\Exceptions\Halt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Markup;

use function Sylament\Support\blank;

abstract class EditRecord extends Page
{
    use HasRelationManagers;
    use HasUnsavedDataChangesAlert;
    use InteractsWithRecord;

    protected ?string $template = '@SylamentPanels/partials/EditRecord/index.html.twig';

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private ?EntityManagerInterface $entityManager = null;

    #[Required]
    public function setRecordPersistence(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array<string, string> $parentRecords
     */
    public function mount(int|string $record, array $parentRecords = []): void
    {
        $this->mountRecord($record, $parentRecords);

        $this->fillForm();
        $this->rememberData();
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $schema = $this->getSchema('form');
        $schema->fill($this->mutateFormDataBeforeFill($schema->getStateFromRecord($this->getRecord())));

        $this->callHook('afterFill');
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
     * The relation managers this record shows, in the one component the four
     * arms answer with. The form chrome stays in the template, so this is
     * narrower than Filament's `content()`, which owns the whole page body.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getRelationManagersContentComponent(),
        ]);
    }

    /**
     * @return array<string>
     */
    public function schemas(): array
    {
        return [
            'form',
            'content',
        ];
    }

    #[LiveAction]
    public function save(): ?RedirectResponse
    {
        $schema = $this->getSchema('form');

        try {
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
        } catch (Halt) {
            return null;
        }

        $this->rememberData();

        $this->getSavedNotification()?->send();

        $redirectUrl = $this->getRedirectUrl();

        return $redirectUrl === null ? null : new RedirectResponse($redirectUrl);
    }

    /**
     * @return list<string>
     */
    public function getPageClasses(): array
    {
        return [
            'sy-pa-resource-edit-record-page',
            'sy-pa-resource-' . str_replace('/', '-', $this->getResourceInstance()->getSlug()),
            'sy-pa-resource-record-' . $this->getRecordKey(),
        ];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Save')
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
        return $action instanceof DeleteAction ? $this->getResourceInstance()->getIndexUrl([...$this->parentRecords]) : null;
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        $resource = $this->getResourceInstance();

        return match (true) {
            $action instanceof CreateAction => $resource->form(...),
            // The page's own edit modal is the page's own form: one tree, one
            // state path, so the two views cannot drift apart.
            $action instanceof EditAction => static fn (Schema $schema): Schema => $schema->components([EmbeddedSchema::make('form')]),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $resource->infolist($resource->form($schema)),
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

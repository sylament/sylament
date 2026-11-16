<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Schemas\Components\Group;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A form whose file fields each store an upload their own way when `save()` records the form's
 * state in the request's {@see HookLog}, as {@see FileUploadFixtures::savedState()} reads it:
 * under a `/docs` directory keeping its name at `documentName`, at the storage root, under the
 * client's name, under a generated name, privately, by a move, on `secondary.storage` though
 * asked to move, not at all, through no store step, through a store step of its own, under a
 * generator that names nothing, as a multiple field keeping its names at `albumNames`, reordered
 * by a callback that records, under a reorder callback a closure turns off, and inside a
 * `details` group keeping its name by a string path and by a closure. `mount()` fills the form
 * with its defaults.
 */
#[AsLiveComponent(name: 'Forms:SavingUploadForm', template: '@Fixtures/forms/SavingUploadForm.html.twig')]
class SavingUploadForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function mount(): void
    {
        $this->getSchema('form')->fill();
    }

    public function schemas(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                FileUpload::make('document')->directory('/docs')->storeFileNamesIn('documentName'),
                FileUpload::make('rooted'),
                FileUpload::make('preserved')->preserveFilenames(),
                FileUpload::make('named')->getUploadedFileNameForStorageUsing(static fn (TemporaryUploadedFile $file): string => 'named-' . $file->getClientOriginalName()),
                FileUpload::make('private')->visibility('private'),
                FileUpload::make('moved')->moveFiles(),
                FileUpload::make('elsewhere')->disk('secondary.storage')->moveFiles(),
                FileUpload::make('held')->storeFiles(false),
                FileUpload::make('discarded')->saveUploadedFileUsing(null),
                FileUpload::make('replaced')->saveUploadedFileUsing(static fn (TemporaryUploadedFile $file): string => 'replaced/' . $file->getClientOriginalName()),
                FileUpload::make('nameless')->getUploadedFileNameForStorageUsing(static fn (): string => ''),
                FileUpload::make('album')->multiple()->storeFileNamesIn('albumNames'),
                FileUpload::make('ordered')
                    ->multiple()
                    ->reorderable()
                    ->reorderUploadedFilesUsing(function (array $rawState): array {
                        $this->hookLog->record('reordered');

                        return array_reverse($rawState, preserve_keys: true);
                    }),
                FileUpload::make('unordered')
                    ->multiple()
                    ->reorderable(static fn (): bool => false)
                    ->reorderUploadedFilesUsing(function (array $rawState): array {
                        $this->hookLog->record('reordered unordered');

                        return $rawState;
                    }),
                Group::make([
                    FileUpload::make('scan')->storeFileNamesIn('scanName'),
                    FileUpload::make('snapshot')->storeFileNamesIn(static fn (): string => 'snapshotName'),
                ])->statePath('details'),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->hookLog->record(FileUploadFixtures::SAVED . json_encode($this->getSchema('form')->getState(), JSON_THROW_ON_ERROR));
    }
}

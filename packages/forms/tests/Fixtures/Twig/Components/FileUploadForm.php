<?php

declare(strict_types=1);

namespace Sylament\Forms\Tests\Fixtures\Twig\Components;

use Sylament\Forms\Components\FileUpload;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * The live host the upload lifecycle is driven through: `cover` is the single-file field and
 * `attachments` the multiple one, and every configuration a case needs is a prop rather than a
 * fixture of its own — one round-trip rig, seeded per test at mount.
 */
#[AsLiveComponent(name: 'FileUploadForm', template: '@FormsFixtures/components/FileUploadForm.html.twig')]
class FileUploadForm implements InteractsWithSchemas
{
    use DefaultActionTrait;
    use HasSchemas;

    /**
     * @var array<string, mixed>
     */
    #[LiveProp(writable: true)]
    public array $data = [];

    /**
     * The bag `getState()` assembles on `save`, kept as a prop so it survives the round-trip.
     *
     * @var array<string, mixed>
     */
    #[LiveProp]
    public array $savedState = [];

    /**
     * The paths `deleteUploadedFileUsing` was handed, and the count of `afterStateUpdated` firings.
     *
     * @var list<string>
     */
    #[LiveProp]
    public array $deletedFiles = [];

    #[LiveProp]
    public int $stateUpdates = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[LiveProp]
    public ?array $seed = null;

    #[LiveProp]
    public string $coverDisk = 'test.storage';

    #[LiveProp]
    public bool $isDisabled = false;

    #[LiveProp]
    public bool $shouldPreserveFilenames = false;

    #[LiveProp]
    public bool $usesCustomFileName = false;

    #[LiveProp]
    public bool $shouldMoveFiles = false;

    #[LiveProp]
    public bool $isReorderable = false;

    #[LiveProp]
    public bool $isRequired = false;

    #[LiveProp]
    public ?string $visibility = null;

    #[LiveProp]
    public ?string $fileNamesStatePath = null;

    #[LiveProp]
    public ?int $maxSize = null;

    #[LiveProp]
    public ?int $minSize = null;

    #[LiveProp]
    public ?int $minFiles = null;

    /**
     * @var list<string>|null
     */
    #[LiveProp]
    public ?array $acceptedFileTypes = null;

    /**
     * The initial fill runs after the props are set, not in `mount()`: `ComponentFactory` assigns
     * the remaining props *after* calling `mount()`, and every prop below configures the schema
     * this fill hydrates.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    #[PostMount]
    public function fillFromSeed(array $data): array
    {
        $this->fill($this->seed);

        return $data;
    }

    public function schema(Schema $schema): Schema
    {
        $cover = FileUpload::make('cover')
            ->disk($this->coverDisk)
            ->directory('covers')
            ->disabled($this->isDisabled)
            ->preserveFilenames($this->shouldPreserveFilenames)
            ->moveFiles($this->shouldMoveFiles)
            ->visibility($this->visibility)
            ->storeFileNamesIn($this->fileNamesStatePath)
            ->maxSize($this->maxSize)
            ->minSize($this->minSize)
            ->minFiles($this->minFiles)
            ->required($this->isRequired)
            ->deleteUploadedFileUsing(function (string|TemporaryUploadedFile $file): void {
                $this->deletedFiles[] = (string) $file;
            })
            ->afterStateUpdated(function (): void {
                $this->stateUpdates++;
            });

        if ($this->acceptedFileTypes !== null) {
            $cover->acceptedFileTypes($this->acceptedFileTypes);
        }

        if ($this->usesCustomFileName) {
            $cover->getUploadedFileNameForStorageUsing(
                static fn (TemporaryUploadedFile $file): string => 'x.' . $file->getClientOriginalExtension(),
            );
        }

        return $schema
            ->statePath('data')
            ->components([
                $cover,
                FileUpload::make('attachments')
                    ->multiple()
                    ->disk('test.storage')
                    ->directory('attachments')
                    ->reorderable($this->isReorderable)
                    ->disabled($this->isDisabled),
            ]);
    }

    #[LiveAction]
    public function save(): void
    {
        $this->savedState = $this->getSchema()->getState();
    }
}

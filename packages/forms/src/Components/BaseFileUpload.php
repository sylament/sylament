<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use DateTimeImmutable;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;
use LogicException;
use Sylament\Forms\Uploads\TemporaryUploadedFile;
use Sylament\Forms\Uploads\TemporaryUploads;
use Sylament\Forms\Validation\MinFileSize;
use Sylament\Schemas\Components\Contracts\AcceptsUploadedFiles;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\FileUploadStateCast;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Sylament\Support\Filesystem\FilesystemRegistry;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Traversable;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\sanitize_url;
use function Sylament\Support\wrap;

/**
 * The upload lifecycle every file field shares: a browser upload is validated against this
 * field's own file constraints and held by {@see TemporaryUploads} as a signed token in raw
 * state; the collection constraints run at save; and `saveUploadedFiles()` then resolves each
 * token onto the field's storage, leaving the stored path in state.
 *
 * The base is abstract because it renders nothing — {@see FileUpload} is the shipped field — but
 * every method below is the base's, so a consumer's own subclass inherits the whole lifecycle.
 */
abstract class BaseFileUpload extends Field implements AcceptsUploadedFiles
{
    /**
     * @var iterable<string>|Closure|null
     */
    protected iterable|Closure|null $acceptedFileTypes = null;

    protected bool|Closure $isDeletable = true;

    protected bool|Closure $isDownloadable = false;

    protected bool|Closure $isOpenable = false;

    protected bool|Closure $isPasteable = true;

    protected bool|Closure $isPreviewable = true;

    protected bool|Closure $isReorderable = false;

    protected string|Closure|null $directory = null;

    protected string|Closure|null $diskName = null;

    protected ?FilesystemOperator $cachedDisk = null;

    protected ?string $cachedDiskName = null;

    protected bool|Closure $isMultiple = false;

    protected int|Closure|null $maxSize = null;

    protected int|Closure|null $minSize = null;

    protected int|Closure|null $maxParallelUploads = null;

    protected int|Closure|null $maxFiles = null;

    protected int|Closure|null $minFiles = null;

    protected bool|Closure $shouldPreserveFilenames = false;

    protected bool|Closure $shouldMoveFiles = false;

    protected bool|Closure $shouldStoreFiles = true;

    protected bool|Closure $shouldFetchFileInformation = true;

    protected bool|Closure $shouldPreventFilePathTampering = false;

    protected ?Closure $allowFilePathUsing = null;

    protected string|Closure|null $fileNamesStatePath = null;

    protected string|Closure|null $visibility = null;

    protected ?Closure $deleteUploadedFileUsing = null;

    protected ?Closure $getUploadedFileNameForStorageUsing = null;

    protected ?Closure $getUploadedFileUsing = null;

    protected ?Closure $getOpenableFileUrlUsing = null;

    protected ?Closure $getDownloadableFileUrlUsing = null;

    protected ?Closure $reorderUploadedFilesUsing = null;

    protected ?Closure $saveUploadedFileUsing = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $imageAspectRatio = null;

    private ?TemporaryUploads $temporaryUploads = null;

    private ?FilesystemRegistry $filesystemRegistry = null;

    private ?ValidatorInterface $validator = null;

    #[Required]
    public function setTemporaryUploads(TemporaryUploads $temporaryUploads): void
    {
        $this->temporaryUploads = $temporaryUploads;
    }

    #[Required]
    public function setFilesystemRegistry(FilesystemRegistry $filesystemRegistry): void
    {
        $this->filesystemRegistry = $filesystemRegistry;
    }

    #[Required]
    public function setValidator(ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    public function getTemporaryUploads(): TemporaryUploads
    {
        return $this->temporaryUploads ?? throw new LogicException(sprintf(
            'The [%s] field was built with `new` and cannot upload files; register it as a service so the temporary-upload store reaches it.',
            $this->getName() ?? static::class,
        ));
    }

    public function getFilesystemRegistry(): FilesystemRegistry
    {
        return $this->filesystemRegistry ?? throw new LogicException(sprintf(
            'The [%s] field was built with `new` and cannot upload files; register it as a service so the filesystem registry reaches it.',
            $this->getName() ?? static::class,
        ));
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->validator ??= Validation::createValidator();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The disk-existence check runs only when state is hydrated from the record, never when a
        // live request updates it, so it is not a boundary for a submitted path —
        // `preventFilePathTampering()` is.
        $this->afterStateHydrated(static function (BaseFileUpload $component): void {
            $component->hydrateFiles();
        });

        $this->beforeStateDehydrated(static function (BaseFileUpload $component): void {
            $component->saveUploadedFiles();
        }, shouldUpdateValidatedStateAfter: true);

        $this->getUploadedFileUsing(
            /**
             * @param string|array<array-key, mixed>|null $storedFileNames
             *
             * @return array<string, mixed>|null
             */
            static fn (BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array => $component->getUploadedFile($file, $storedFileNames),
        );

        $this->getUploadedFileNameForStorageUsing(static fn (BaseFileUpload $component, TemporaryUploadedFile $file): string => $component->shouldPreserveFilenames()
            ? $file->getClientOriginalName()
            : new Ulid()->toBase32() . '.' . $file->getClientOriginalExtension());

        $this->saveUploadedFileUsing(static fn (BaseFileUpload $component, TemporaryUploadedFile $file): ?string => $component->saveUploadedFile($file));
    }

    /**
     * @param iterable<string>|Closure $types
     */
    public function acceptedFileTypes(iterable|Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function deletable(bool|Closure $condition = true): static
    {
        $this->isDeletable = $condition;

        return $this;
    }

    public function directory(string|Closure|null $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function disk(string|Closure|null $name): static
    {
        $this->diskName = $name;

        $this->cachedDisk = null;
        $this->cachedDiskName = null;

        return $this;
    }

    public function downloadable(bool|Closure $condition = true): static
    {
        $this->isDownloadable = $condition;

        return $this;
    }

    public function openable(bool|Closure $condition = true): static
    {
        $this->isOpenable = $condition;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function pasteable(bool|Closure $condition = true): static
    {
        $this->isPasteable = $condition;

        return $this;
    }

    public function previewable(bool|Closure $condition = true): static
    {
        $this->isPreviewable = $condition;

        return $this;
    }

    public function storeFileNamesIn(string|Closure|null $statePath): static
    {
        $this->fileNamesStatePath = $statePath;

        return $this;
    }

    public function preserveFilenames(bool|Closure $condition = true): static
    {
        // Security: a client-supplied filename on a local or public storage can be a `.php` file
        // the web server executes. `acceptedFileTypes()` validates the sniffed MIME type, never
        // the extension, so only turn this on for trusted users or a storage that serves bytes.
        $this->shouldPreserveFilenames = $condition;

        return $this;
    }

    public function moveFiles(bool|Closure $condition = true): static
    {
        $this->shouldMoveFiles = $condition;

        return $this;
    }

    public function fetchFileInformation(bool|Closure $condition = true): static
    {
        $this->shouldFetchFileInformation = $condition;

        return $this;
    }

    public function maxSize(int|Closure|null $size): static
    {
        $this->maxSize = $size;

        return $this;
    }

    public function minSize(int|Closure|null $size): static
    {
        $this->minSize = $size;

        return $this;
    }

    public function maxParallelUploads(int|Closure|null $count): static
    {
        $this->maxParallelUploads = $count;

        return $this;
    }

    public function maxFiles(int|Closure|null $count): static
    {
        $this->maxFiles = $count;

        return $this;
    }

    public function minFiles(int|Closure|null $count): static
    {
        $this->minFiles = $count;

        return $this;
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function storeFiles(bool|Closure $condition = true): static
    {
        $this->shouldStoreFiles = $condition;

        return $this;
    }

    public function preventFilePathTampering(bool|Closure $condition = true, ?Closure $allowFilePathUsing = null): static
    {
        $this->shouldPreventFilePathTampering = $condition;
        $this->allowFilePathUsing = $allowFilePathUsing;

        return $this;
    }

    public function visibility(string|Closure|null $visibility): static
    {
        // Security: visibility decides who can read the stored bytes, never what may be stored —
        // `acceptedFileTypes()` and `maxSize()` are what refuse a file, and they run whatever
        // this is set to.
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $ratio
     */
    public function imageAspectRatio(string|array|Closure|null $ratio): static
    {
        $this->imageAspectRatio = $ratio;

        return $this;
    }

    public function deleteUploadedFileUsing(?Closure $callback): static
    {
        $this->deleteUploadedFileUsing = $callback;

        return $this;
    }

    public function getUploadedFileUsing(?Closure $callback): static
    {
        $this->getUploadedFileUsing = $callback;

        return $this;
    }

    public function getOpenableFileUrlUsing(?Closure $callback): static
    {
        $this->getOpenableFileUrlUsing = $callback;

        return $this;
    }

    public function getDownloadableFileUrlUsing(?Closure $callback): static
    {
        $this->getDownloadableFileUrlUsing = $callback;

        return $this;
    }

    public function reorderUploadedFilesUsing(?Closure $callback): static
    {
        $this->reorderUploadedFilesUsing = $callback;

        return $this;
    }

    public function saveUploadedFileUsing(?Closure $callback): static
    {
        $this->saveUploadedFileUsing = $callback;

        return $this;
    }

    public function getUploadedFileNameForStorageUsing(?Closure $callback): static
    {
        // Security: the callback names the file on the storage. Returning the client's own name
        // is what `preserveFilenames()` does, and carries that method's warning.
        $this->getUploadedFileNameForStorageUsing = $callback;

        return $this;
    }

    public function isDeletable(): bool
    {
        return (bool) $this->evaluate($this->isDeletable);
    }

    public function isDownloadable(): bool
    {
        return (bool) $this->evaluate($this->isDownloadable);
    }

    public function isOpenable(): bool
    {
        return (bool) $this->evaluate($this->isOpenable);
    }

    public function isPasteable(): bool
    {
        return (bool) $this->evaluate($this->isPasteable);
    }

    public function isPreviewable(): bool
    {
        return (bool) $this->evaluate($this->isPreviewable);
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    /**
     * @return array<string>|null
     */
    public function getAcceptedFileTypes(): ?array
    {
        $types = $this->evaluate($this->acceptedFileTypes);

        if ($types instanceof Traversable) {
            return iterator_to_array($types);
        }

        return $types;
    }

    public function getDirectory(): ?string
    {
        return $this->evaluate($this->directory);
    }

    public function getDiskName(): string
    {
        $name = $this->evaluate($this->diskName);

        if (filled($name)) {
            return $name;
        }

        return $this->getFilesystemRegistry()->getDefaultDiskName();
    }

    /**
     * Memoised per resolved name, not per instance: `disk()` may name a closure, so the same
     * field can answer for two storages over its lifetime.
     */
    public function getDisk(): FilesystemOperator
    {
        $name = $this->getDiskName();

        if ($this->cachedDisk instanceof FilesystemOperator && $this->cachedDiskName === $name) {
            return $this->cachedDisk;
        }

        $this->cachedDiskName = $name;

        return $this->cachedDisk = $this->getFilesystemRegistry()->get($name);
    }

    public function getMaxFiles(): ?int
    {
        return $this->evaluate($this->maxFiles);
    }

    public function getMinFiles(): ?int
    {
        return $this->evaluate($this->minFiles);
    }

    public function getMaxSize(): ?int
    {
        return $this->evaluate($this->maxSize);
    }

    public function getMinSize(): ?int
    {
        return $this->evaluate($this->minSize);
    }

    public function getMaxParallelUploads(): ?int
    {
        return $this->evaluate($this->maxParallelUploads);
    }

    /**
     * The ceiling PHP itself enforces, in bytes: `min(post_max_size, upload_max_filesize)` with
     * each unset value read as `PHP_INT_MAX`. The static is declared `int|float` because it
     * returns a float past `PHP_INT_MAX`, which the narrowing here takes back.
     */
    public function getMaxUploadSize(): int
    {
        return (int) min(UploadedFile::getMaxFilesize(), PHP_INT_MAX);
    }

    /**
     * Null means "whatever the storage is configured for" — flysystem storages carry their own
     * default visibility, and Filament's `public`/`private` inference from a Laravel disk *name*
     * has no referent here.
     */
    public function getVisibility(): ?string
    {
        return $this->evaluate($this->visibility);
    }

    public function shouldPreserveFilenames(): bool
    {
        return (bool) $this->evaluate($this->shouldPreserveFilenames);
    }

    public function shouldMoveFiles(): bool
    {
        return (bool) $this->evaluate($this->shouldMoveFiles);
    }

    public function shouldFetchFileInformation(): bool
    {
        return (bool) $this->evaluate($this->shouldFetchFileInformation);
    }

    public function shouldStoreFiles(): bool
    {
        return (bool) $this->evaluate($this->shouldStoreFiles);
    }

    public function shouldPreventFilePathTampering(): bool
    {
        return (bool) $this->evaluate($this->shouldPreventFilePathTampering);
    }

    /**
     * @return string|array<string>|null
     */
    public function getImageAspectRatio(): string|array|null
    {
        $ratio = $this->evaluate($this->imageAspectRatio);

        if (is_array($ratio)) {
            return array_values(array_filter(array_map(
                $this->normalizeAspectRatio(...),
                $ratio,
            )));
        }

        return $this->normalizeAspectRatio($ratio);
    }

    protected function calculateAspectRatio(?string $ratio): ?float
    {
        if ($ratio === null) {
            return null;
        }

        $parts = explode(':', $ratio);

        if (count($parts) !== 2) {
            return null;
        }

        [$numerator, $denominator] = $parts;

        if (! is_numeric($numerator) || ! is_numeric($denominator) || ((float) $denominator === 0.0)) {
            return null;
        }

        return (float) $numerator / (float) $denominator;
    }

    protected function normalizeAspectRatio(?string $ratio): ?string
    {
        if (blank($ratio)) {
            return null;
        }

        if (str_contains($ratio, ':')) {
            return $ratio;
        }

        if (str_contains($ratio, '/')) {
            return str_replace('/', ':', $ratio);
        }

        if (is_numeric($ratio)) {
            return $ratio . ':1';
        }

        return null;
    }

    public function hydrateFiles(): void
    {
        $this->rawState(array_filter(
            wrap($this->getRawState()),
            function (mixed $rawFile): bool {
                $file = is_string($rawFile) ? $rawFile : null;

                if (blank($file)) {
                    return false;
                }

                return ! $this->shouldFetchFileInformation() || $this->fileExistsOnDisk($file);
            },
        ));
    }

    protected function fileExistsOnDisk(string $file): bool
    {
        try {
            return $this->getDisk()->fileExists($file);
        } catch (UnableToCheckFileExistence) {
            // A storage that cannot answer is treated as no longer holding the file, so the form
            // renders without it rather than failing the whole hydration.
            return false;
        }
    }

    /**
     * Accept one browser upload: refuse it outright while disabled, answer the transport error
     * for a file the browser could not deliver, then run this field's *file* constraints against
     * the real `UploadedFile` — which is the only moment Symfony's `File`/`Image` validators can
     * run at all, since they need a local readable path a stored flysystem key is not. Only a
     * file that cleared them is written anywhere.
     *
     * @return array<string, mixed>|null
     */
    public function acceptUploadedFile(string $fileKey, UploadedFile $file): ?array
    {
        if ($this->isDisabled()) {
            return null;
        }

        if (! $file->isValid()) {
            return ['ok' => false, 'message' => $file->getErrorMessage()];
        }

        $violations = $this->getValidator()->validate($file, $this->getUploadedFileConstraints());

        if (count($violations) > 0) {
            return ['ok' => false, 'message' => (string) $violations->get(0)->getMessage()];
        }

        $upload = $this->getTemporaryUploads()->store($file, (string) $this->getStatePath());

        $state = wrap($this->getRawState());
        $state[$fileKey] = $upload->getToken();
        $this->rawState($state);
        $this->callAfterStateUpdatedHooks();

        return [
            'ok' => true,
            'fileKey' => $fileKey,
            'name' => $upload->getClientOriginalName(),
            'size' => $upload->getSize(),
            'type' => $upload->getMimeType(),
        ];
    }

    /**
     * The collection set: everything the parent contributes except the per-file constraints,
     * which ran at upload time instead, plus the file-count and path-authorisation rules. The
     * split hangs off this hook rather than off `getValidationConstraints()`, which is
     * `applyValidationMessages(buildValidationConstraints())` and already emits the required
     * constraint — overriding it would render `NotBlank` twice and skip the message layer.
     *
     * @return list<Constraint>
     */
    protected function buildValidationConstraints(): array
    {
        $constraints = array_values(array_filter(
            parent::buildValidationConstraints(),
            static fn (Constraint $constraint): bool => ! $constraint instanceof File,
        ));

        $minFiles = $this->getMinFiles();
        $maxFiles = $this->getMaxFiles();

        if (($minFiles !== null) || ($maxFiles !== null)) {
            $constraints[] = new Count(
                min: ($minFiles === null) ? null : max(0, $minFiles),
                max: ($maxFiles === null) ? null : max(0, $maxFiles),
            );
        }

        if ($this->shouldPreventFilePathTampering()) {
            $constraints[] = $this->getFilePathAuthorizationConstraint();
        }

        return $constraints;
    }

    /**
     * The per-file set, validated against the real `UploadedFile` at upload time. `Image extends
     * File`, so the `instanceof File` split catches a user's own image constraint too.
     *
     * @return list<Constraint>
     */
    public function getUploadedFileConstraints(): array
    {
        $constraints = [];

        $acceptedFileTypes = $this->getAcceptedFileTypes();

        if (filled($acceptedFileTypes)) {
            $constraints[] = new File(mimeTypes: array_values($acceptedFileTypes));
        }

        if (filled($maxSize = $this->getMaxSize())) {
            $constraints[] = new File(maxSize: $maxSize . 'Ki');
        }

        if (filled($minSize = $this->getMinSize())) {
            $constraints[] = new MinFileSize($minSize);
        }

        $ratios = array_values(array_filter(array_map(
            $this->buildAspectRatioConstraint(...),
            wrap($this->getImageAspectRatio()),
        )));

        if (count($ratios) === 1) {
            $constraints[] = $ratios[0];
        } elseif (count($ratios) > 1) {
            $constraints[] = new AtLeastOneOf($ratios);
        }

        return $this->applyValidationMessages([
            ...$constraints,
            ...array_values(array_filter(
                parent::buildValidationConstraints(),
                static fn (Constraint $constraint): bool => $constraint instanceof File,
            )),
        ]);
    }

    protected function buildAspectRatioConstraint(string $ratio): ?Image
    {
        $calculated = $this->calculateAspectRatio($ratio);

        if ($calculated === null) {
            return null;
        }

        return new Image(maxRatio: $calculated, minRatio: $calculated);
    }

    /**
     * The save-time half of `preventFilePathTampering()`: the data bag is client-writable, so a
     * stored path sitting in state has to trace back to the record. Our own tokens are excluded —
     * a token is proof this field validated this file, and it is never a path.
     */
    protected function getFilePathAuthorizationConstraint(): Constraint
    {
        return new Callback(function (mixed $value, ExecutionContextInterface $context): void {
            $originalPaths = $this->getOriginalFilePaths();

            foreach (wrap($value) as $file) {
                if (is_string($file) && ! TemporaryUploads::isToken($file) && ! $this->isFilePathAuthorized($file, $originalPaths)) {
                    $context->buildViolation('The submitted file path is not authorised.')->addViolation();

                    return;
                }
            }
        });
    }

    /**
     * @param array<string>|null $originalPaths
     */
    public function isFilePathAuthorized(string $file, ?array $originalPaths = null): bool
    {
        if (in_array($file, $originalPaths ?? $this->getOriginalFilePaths(), strict: true)) {
            return true;
        }

        if ($this->allowFilePathUsing instanceof Closure) {
            return (bool) $this->evaluateCallback($this->allowFilePathUsing, ['file' => $file]);
        }

        return false;
    }

    /**
     * The record's current value for this field, which is its original at gate time: the record is
     * only written after validation runs (`Schema::getData()` calls `getState()` first). An array
     * record is read by key — it supplies state and cannot be mapped — an object through the
     * record reader, and no record, no such property or an uninitialized one authorises nothing.
     *
     * @return list<string>
     */
    public function getOriginalFilePaths(): array
    {
        $record = $this->getRecord();
        $name = $this->getName();

        if ($name === null) {
            return [];
        }

        try {
            $original = match (true) {
                is_array($record) => $record[$name] ?? null,
                is_object($record) => $this->getPropertyAccessor()->getFromRecord($record, $name),
                default => null,
            };
        } catch (AccessException) {
            return [];
        }

        $paths = [];

        foreach (wrap($original) as $rawPath) {
            $path = is_string($rawPath) ? $rawPath : null;

            if (filled($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * The payload the template renders into the controller's `uploadedFiles` value: one entry per
     * raw-state key, `null` where there is nothing to render — a temporary upload the browser
     * still holds locally, or a path the tampering gate refuses.
     *
     * @return array<string, array<string, mixed>|null>|null
     */
    public function getUploadedFiles(): ?array
    {
        $files = [];

        $shouldCheckAuthorization = $this->shouldPreventFilePathTampering();
        $originalPaths = $shouldCheckAuthorization ? $this->getOriginalFilePaths() : [];
        $callback = $this->getUploadedFileUsing;
        $storedFileNames = $this->getStoredFileNames();
        $openableFileUrlCallback = $this->isOpenable() ? $this->getOpenableFileUrlUsing : null;
        $downloadableFileUrlCallback = $this->isDownloadable() ? $this->getDownloadableFileUrlUsing : null;

        foreach (wrap($this->getRawState()) as $rawFileKey => $file) {
            $fileKey = (string) $rawFileKey;

            if (! is_string($file) || TemporaryUploads::isToken($file)) {
                $files[$fileKey] = null;

                continue;
            }

            if ($shouldCheckAuthorization && ! $this->isFilePathAuthorized($file, $originalPaths)) {
                $files[$fileKey] = null;

                continue;
            }

            if (! $callback instanceof Closure) {
                return [$fileKey => null];
            }

            $uploadedFile = $this->evaluateCallback($callback, [
                'file' => $file,
                'storedFileNames' => $storedFileNames,
            ]);

            if (! is_array($uploadedFile) || $uploadedFile === []) {
                $files[$fileKey] = null;

                continue;
            }

            if (array_key_exists('url', $uploadedFile)) {
                $uploadedFile['url'] = $this->sanitizeFileUrl($uploadedFile['url']);
            }

            if ($openableFileUrlCallback instanceof Closure) {
                $openableUrl = $this->sanitizeFileUrl($this->evaluateCallback($openableFileUrlCallback, [
                    'file' => $file,
                    'storedFileNames' => $storedFileNames,
                ]));

                if ($openableUrl !== null) {
                    $uploadedFile['openableUrl'] = $openableUrl;
                }
            }

            if ($downloadableFileUrlCallback instanceof Closure) {
                $downloadableUrl = $this->sanitizeFileUrl($this->evaluateCallback($downloadableFileUrlCallback, [
                    'file' => $file,
                    'storedFileNames' => $storedFileNames,
                ]));

                if ($downloadableUrl !== null) {
                    $uploadedFile['downloadableUrl'] = $downloadableUrl;
                }
            }

            /** @var array<string, mixed> $uploadedFile */
            $files[$fileKey] = $uploadedFile;
        }

        return $files;
    }

    /**
     * `evaluate()` is generic over the value it is handed, so a bare `Closure` argument infers a
     * `Closure` return as well. Every callback this class stores goes through here instead, whose
     * `mixed` return leaves the narrowing to the call site that knows what it asked for.
     *
     * @param array<string, mixed> $namedInjections
     */
    private function evaluateCallback(Closure $callback, array $namedInjections = []): mixed
    {
        return $this->evaluate($callback, $namedInjections);
    }

    private function sanitizeFileUrl(mixed $url): ?string
    {
        return sanitize_url(is_string($url) ? $url : null);
    }

    /**
     * The metadata one stored file renders from. `$storedFileNames` comes from a client-writable
     * state path, so it is typed as loosely as it really is.
     *
     * @param string|array<array-key, mixed>|null $storedFileNames
     *
     * @return array{name: string, size: int, type: ?string, url: ?string}|null
     */
    public function getUploadedFile(string $file, string|array|null $storedFileNames): ?array
    {
        $storage = $this->getDisk();

        $size = 0;
        $type = null;

        if ($this->shouldFetchFileInformation()) {
            try {
                $size = $storage->fileSize($file);
                $type = $storage->mimeType($file);
            } catch (UnableToRetrieveMetadata | UnableToCheckFileExistence) {
                return null;
            }
        }

        $name = $this->isMultiple()
            ? (is_array($storedFileNames) ? ($storedFileNames[$file] ?? null) : null)
            : $storedFileNames;
        $name = is_string($name) ? $name : null;

        return [
            'name' => filled($name) ? $name : basename($file),
            'size' => $size,
            'type' => $type,
            'url' => $this->getUploadedFileUrl($file),
        ];
    }

    protected function getUploadedFileUrl(string $file): ?string
    {
        $storage = $this->getDisk();
        $visibility = $this->getVisibility();

        try {
            // Security: an unreadable visibility is read as private, and the private arm never
            // falls through to publicUrl() — otherwise a storage that cannot mint a temporary URL
            // would hand the browser a public link to a file the user marked private.
            $visibility ??= $storage->visibility($file);
        } catch (UnableToRetrieveMetadata) {
            $visibility = Visibility::PRIVATE;
        }

        if ($visibility === Visibility::PRIVATE) {
            if (! method_exists($storage, 'temporaryUrl')) {
                return null;
            }

            try {
                return sanitize_url($storage->temporaryUrl(
                    $file,
                    new DateTimeImmutable('+' . $this->getFilesystemRegistry()->getTemporaryUrlExpiryMinutes() . ' minutes'),
                ));
            } catch (UnableToGenerateTemporaryUrl) {
                return null;
            }
        }

        if (! method_exists($storage, 'publicUrl')) {
            return null;
        }

        try {
            return sanitize_url($storage->publicUrl($file));
        } catch (UnableToGeneratePublicUrl) {
            return null;
        }
    }

    /**
     * Browser-callable. Removes one entry from state, deleting the bytes when the entry is one of
     * our temporary uploads. Under the tampering gate an unauthorised *path* is refused — but our
     * own tokens are excluded from that test, because a freshly minted token is never among the
     * record's original paths and the gate would otherwise refuse the user's own upload and leak
     * the temporary file.
     */
    #[ExposedLiveComponentMethod]
    public function removeUploadedFile(string $fileKey): string|TemporaryUploadedFile|null
    {
        if ($this->isDisabled() || ! $this->isDeletable()) {
            return null;
        }

        $files = wrap($this->getRawState());
        $rawFile = $files[$fileKey] ?? null;
        $file = is_string($rawFile) ? $rawFile : null;

        if (blank($file)) {
            return null;
        }

        if (
            ! TemporaryUploads::isToken($file)
            && $this->shouldPreventFilePathTampering()
            && ! $this->isFilePathAuthorized($file)
        ) {
            return null;
        }

        $removed = $file;

        if (TemporaryUploads::isToken($file)) {
            $upload = $this->getTemporaryUploads()->resolve($file, (string) $this->getStatePath());
            $upload?->delete();
            $removed = $upload ?? $file;
        } else {
            $this->removeStoredFileName($file);
        }

        unset($files[$fileKey]);

        $this->rawState($files);
        $this->callAfterStateUpdatedHooks();

        return $removed;
    }

    /**
     * Browser-callable. Returns nothing where Filament returns `$this`: our seam serialises a
     * result into a browser event, and a component object has no business travelling there.
     */
    #[ExposedLiveComponentMethod]
    public function deleteUploadedFile(string $fileKey): void
    {
        $file = $this->removeUploadedFile($fileKey);

        if ($file === null) {
            return;
        }

        $callback = $this->deleteUploadedFileUsing;

        if (! $callback instanceof Closure) {
            return;
        }

        $this->evaluateCallback($callback, ['file' => $file]);
    }

    /**
     * Browser-callable. Reorders raw state into the key order the browser sends; a key the client
     * does not know about — one added to state while the reorder was in flight — sorts first, as
     * Filament's does.
     *
     * @param array<array-key> $fileKeys
     */
    #[ExposedLiveComponentMethod]
    public function reorderUploadedFiles(array $fileKeys): void
    {
        if ($this->isDisabled() || ! $this->isReorderable()) {
            return;
        }

        $positions = [];

        foreach (array_values($fileKeys) as $position => $fileKey) {
            if (! is_string($fileKey)) {
                return;
            }

            $positions[$fileKey] = $position;
        }

        $rawState = wrap($this->getRawState());

        uksort(
            $rawState,
            static fn (string|int $a, string|int $b): int => ($positions[$a] ?? -1) <=> ($positions[$b] ?? -1),
        );

        $this->rawState($rawState);
        $this->callAfterStateUpdatedHooks();
    }

    /**
     * The store pass, run from `beforeStateDehydrated`. Every token is resolved against this
     * field's own state path — a token minted for another field resolves to nothing and its entry
     * is dropped — and a plain string is a path that is already stored, passed through untouched.
     */
    public function saveUploadedFiles(): void
    {
        if (blank($this->getRawState())) {
            $this->rawState([]);

            return;
        }

        if (! $this->shouldStoreFiles()) {
            return;
        }

        $statePath = (string) $this->getStatePath();
        $callback = $this->saveUploadedFileUsing;
        $rawState = [];

        foreach (wrap($this->getRawState()) as $fileKey => $rawFile) {
            $file = is_string($rawFile) ? $rawFile : null;

            if (blank($file)) {
                continue;
            }

            if (! TemporaryUploads::isToken($file)) {
                // A path already on the storage. Like any live-bag value this string is
                // client-controlled; `preventFilePathTampering()` is what authorises it.
                $rawState[$fileKey] = $file;

                continue;
            }

            $upload = $this->getTemporaryUploads()->resolve($file, $statePath);

            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            if (! $callback instanceof Closure) {
                $upload->delete();

                continue;
            }

            $evaluated = $this->evaluateCallback($callback, ['file' => $upload]);
            $storedFile = is_string($evaluated) ? $evaluated : null;

            if (blank($storedFile)) {
                continue;
            }

            $this->storeFileName($storedFile, $upload->getClientOriginalName());
            $upload->delete();

            $rawState[$fileKey] = $storedFile;
        }

        if ($this->isReorderable() && ($reorder = $this->reorderUploadedFilesUsing) instanceof Closure) {
            $reordered = $this->evaluateCallback($reorder, ['rawState' => $rawState]);

            if (is_array($reordered)) {
                $rawState = $reordered;
            }
        }

        $this->rawState($rawState);
        $this->callAfterStateUpdatedHooks();
    }

    /**
     * The default store step: move the bytes when the temporary storage is this field's own and
     * `moveFiles()` asked for it, otherwise stream them across. A storage failure propagates —
     * a file the user was told was saved must not vanish silently.
     */
    public function saveUploadedFile(TemporaryUploadedFile $file): ?string
    {
        if (! $file->exists()) {
            return null;
        }

        $storage = $this->getDisk();
        $visibility = $this->getVisibility();
        $newPath = mb_trim($this->getDirectory() . '/' . $this->getUploadedFileNameForStorage($file), '/');

        if ($this->shouldMoveFiles() && $this->getDiskName() === $file->getDiskName()) {
            $storage->move($file->getPath(), $newPath);
        } else {
            $storage->writeStream($newPath, $file->readStream(), $visibility === null ? [] : ['visibility' => $visibility]);
            $file->delete();
        }

        if ($visibility !== null) {
            try {
                $storage->setVisibility($newPath, $visibility);
            } catch (UnableToSetVisibility) {
                // A storage with no visibility notion keeps the file it just wrote.
            }
        }

        return $newPath;
    }

    public function getUploadedFileNameForStorage(TemporaryUploadedFile $file): string
    {
        $callback = $this->getUploadedFileNameForStorageUsing;
        $evaluated = $callback instanceof Closure ? $this->evaluateCallback($callback, ['file' => $file]) : null;
        $name = is_string($evaluated) ? $evaluated : null;

        if (blank($name)) {
            throw new LogicException(sprintf(
                'The [%s] field could not name the file to store: `getUploadedFileNameForStorageUsing()` returned no filename.',
                $this->getName() ?? static::class,
            ));
        }

        return $name;
    }

    /**
     * The absolute sibling path the original filenames dehydrate to. The write and read below use
     * the raw declaration instead, because `Get`/`Set` resolve a relative path themselves —
     * Filament's asymmetry, kept.
     */
    public function getFileNamesStatePath(): ?string
    {
        $statePath = $this->evaluate($this->fileNamesStatePath);

        if (blank($statePath)) {
            return null;
        }

        return $this->resolveRelativeStatePath($statePath);
    }

    public function storeFileName(string $file, string $fileName): void
    {
        $statePath = $this->evaluate($this->fileNamesStatePath);

        if (blank($statePath)) {
            return;
        }

        $set = $this->makeSetUtility();

        if (! $this->isMultiple()) {
            $set($statePath, $fileName);

            return;
        }

        $fileNames = $this->makeGetUtility()($statePath);
        $fileNames = is_array($fileNames) ? $fileNames : [];
        $fileNames[$file] = $fileName;

        $set($statePath, $fileNames);
    }

    public function removeStoredFileName(string $file): void
    {
        $statePath = $this->evaluate($this->fileNamesStatePath);

        if (blank($statePath)) {
            return;
        }

        $set = $this->makeSetUtility();

        if (! $this->isMultiple()) {
            $set($statePath, null);

            return;
        }

        $fileNames = $this->makeGetUtility()($statePath);
        $fileNames = is_array($fileNames) ? $fileNames : [];

        unset($fileNames[$file]);

        $set($statePath, $fileNames);
    }

    /**
     * @return string|array<string, string>|null
     */
    public function getStoredFileNames(): string|array|null
    {
        $statePath = $this->evaluate($this->fileNamesStatePath);
        $rawState = filled($statePath) ? $this->makeGetUtility()($statePath) : null;

        if (blank($rawState)) {
            return $this->isMultiple() ? [] : null;
        }

        if (is_array($rawState)) {
            /** @var array<string, string> $rawState */
            return $rawState;
        }

        return is_string($rawState) ? $rawState : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStateToDehydrate(mixed $state): array
    {
        $state = parent::getStateToDehydrate($state);

        $fileNamesStatePath = $this->getFileNamesStatePath();

        if (filled($fileNamesStatePath)) {
            $state[$fileNamesStatePath] = $this->getStoredFileNames();
        }

        return $state;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            new FileUploadStateCast($this->isMultiple()),
        ];
    }
}

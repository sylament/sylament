<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use DOMElement;
use JsonException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use LogicException;
use Sylament\Actions\Testing\Testable;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The files a file-upload test sends, from `assets/uploads/` (`pixel.png` 1x1, `wide.png` 2x1,
 * `large.png` over 1 KiB, `note.txt`), the two requests a file field's controller makes (an
 * upload and a call of one of the field's methods), the Stimulus values a rendered file field
 * hands its controller, decoded as the controller reads them, the temporary and the stored
 * files a disk holds, a file on the primary storage aged by hand, and the state a host's save
 * recorded in the request's {@see HookLog} under {@see self::SAVED}.
 */
final class FileUploadFixtures
{
    public const string SAVED = 'saved: ';

    private const string VALUE_PREFIX = 'data-sylament--forms--file-upload-';

    public static function path(string $fixture): string
    {
        return dirname(__DIR__, 2) . '/assets/uploads/' . $fixture;
    }

    public static function file(string $fixture, ?string $clientName = null, ?string $clientMimeType = null, ?int $error = null): UploadedFile
    {
        return new UploadedFile(self::path($fixture), $clientName ?? $fixture, $clientMimeType, $error, test: true);
    }

    public static function upload(Testable $tester, string $field, string $fileKey, UploadedFile $file): Testable
    {
        return $tester->call(
            'uploadSchemaComponentFile',
            ['componentKey' => "form.{$field}", 'fileKey' => $fileKey],
            ["sylament_upload_{$fileKey}" => $file],
        );
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public static function callMethod(Testable $tester, string $field, string $method, array $arguments): Testable
    {
        return $tester->call(
            'callSchemaComponentMethod',
            ['componentKey' => "form.{$field}", 'method' => $method, 'arguments' => $arguments],
        );
    }

    /**
     * @return array<string>
     */
    public static function temporaryFiles(FilesystemOperator $disk): array
    {
        if (!$disk->directoryExists('sylament-tmp')) {
            return [];
        }

        return $disk->listContents('sylament-tmp')->map(static fn (StorageAttributes $item): string => $item->path())->toArray();
    }

    /**
     * @return array<string>
     */
    public static function storedFiles(FilesystemOperator $disk): array
    {
        return $disk->listContents('', deep: true)
            ->filter(static fn (StorageAttributes $item): bool => $item->isFile() && !str_starts_with($item->path(), 'sylament-tmp/'))
            ->map(static fn (StorageAttributes $item): string => $item->path())
            ->toArray();
    }

    /**
     * Set a file on the primary storage to have last changed `$hours` ago, as a sweep reads it.
     */
    public static function age(string $path, int $hours): void
    {
        touch(WorkerDirectory::path() . '/storage/primary/' . $path, time() - $hours * 3600);
    }

    /**
     * Every value on the field's controller element, by its camel-cased Stimulus name: a JSON
     * value decoded, any other string as written, or every value as written when `$decode` is off.
     *
     * @return array<string, mixed>
     */
    public static function values(Crawler $crawler, string $statePath, bool $decode = true): array
    {
        $root = $crawler->filter(Selectors::fileUpload($statePath));

        if ($root->count() !== 1) {
            throw new LogicException(sprintf('Expected one file upload bound to [%s], found %d.', $statePath, $root->count()));
        }

        $node = $root->getNode(0);

        if (!$node instanceof DOMElement) {
            throw new LogicException('The file upload root is not an element.');
        }

        $values = [];

        foreach ($node->attributes as $attribute) {
            $name = $attribute->name;

            if (!str_starts_with($name, self::VALUE_PREFIX) || !str_ends_with($name, '-value')) {
                continue;
            }

            $key = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', mb_substr($name, mb_strlen(self::VALUE_PREFIX), -mb_strlen('-value'))))));

            if (!$decode) {
                $values[$key] = $attribute->value;

                continue;
            }

            try {
                $values[$key] = json_decode($attribute->value, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $values[$key] = $attribute->value;
            }
        }

        return $values;
    }

    /**
     * The state the last save of the request recorded.
     *
     * @return array<string, mixed>
     */
    public static function savedState(HookLog $hookLog): array
    {
        foreach (array_reverse($hookLog->hooks) as $hook) {
            if (str_starts_with($hook, self::SAVED)) {
                $state = json_decode(mb_substr($hook, mb_strlen(self::SAVED)), true, flags: JSON_THROW_ON_ERROR);

                if (!is_array($state)) {
                    throw new LogicException('The saved state is not an array.');
                }

                /** @var array<string, mixed> $state */
                return $state;
            }
        }

        throw new LogicException('The request saved nothing.');
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Schemas;

use Sylament\Forms\Components\Field;
use Sylament\Schemas\Components\Contracts\AcceptsUploadedFiles;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * A field that accepts every upload it is handed, recording the file key and the file's
 * name in the request's {@see HookLog}.
 */
class UploadReceivingField extends Field implements AcceptsUploadedFiles
{
    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
    }

    public function acceptUploadedFile(string $fileKey, UploadedFile $file): array
    {
        $this->hookLog->record("upload: {$fileKey} {$file->getClientOriginalName()}");

        return ['ok' => true, 'fileKey' => $fileKey, 'name' => $file->getClientOriginalName()];
    }
}

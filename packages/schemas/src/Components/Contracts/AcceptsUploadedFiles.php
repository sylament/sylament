<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Contracts;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface AcceptsUploadedFiles
{
    /**
     * Accept one browser upload for this component. Returns the JSON-safe result the browser
     * receives — `['ok' => true, 'fileKey' => …, 'name' => …, 'size' => …, 'type' => …]` once the
     * file is validated and held, `['ok' => false, 'message' => …]` when a constraint refused it
     * (nothing was written), `null` when the component refuses outright (disabled, not mounted).
     *
     * @return array<string, mixed>|null
     */
    public function acceptUploadedFile(string $fileKey, UploadedFile $file): ?array;
}

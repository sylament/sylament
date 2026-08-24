<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Components;

use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Contracts\AcceptsUploadedFiles;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A minimal {@see AcceptsUploadedFiles} implementor: records what the upload action
 * handed it in its own raw state and answers the browser with the accepted file key.
 * A disabled instance refuses outright, the way a real field does. Renders nothing —
 * a component with no template is skipped by the host's render.
 */
class UploadAccepting extends Component implements AcceptsUploadedFiles
{
    public static function make(string $name): static
    {
        return new static()->statePath($name);
    }

    public function acceptUploadedFile(string $fileKey, UploadedFile $file): ?array
    {
        if ($this->isDisabled()) {
            return null;
        }

        $this->rawState([
            'fileKey' => $fileKey,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        return ['ok' => true, 'fileKey' => $fileKey];
    }
}

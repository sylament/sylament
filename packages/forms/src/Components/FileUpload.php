<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Support\Components\Concerns\HasAlignment;

use function Sylament\Support\filled;
use function Sylament\Support\wrap;

/**
 * The shipped file field: {@see BaseFileUpload}'s lifecycle behind a FilePond control, whose whole
 * configuration travels to the browser as Stimulus values on the partial's root.
 *
 * The crop, resize, transform, exif and image-editor families are not shipped — a missing method
 * fails as loudly as a throwing one and leaves nothing half-working — so `avatar()` is reduced to
 * the calls that still mean something without them.
 */
class FileUpload extends BaseFileUpload
{
    use HasAlignment;
    use HasExtraInputAttributes;

    protected string|Closure|null $template = '@SylamentForms/partials/FileUpload/index.html.twig';

    protected string|Closure|null $imagePreviewHeight = null;

    protected bool|Closure $isAvatar = false;

    protected string|int|float|Closure|null $itemPanelAspectRatio = null;

    protected string|Closure $loadingIndicatorPosition = 'right';

    protected string|Closure|null $panelAspectRatio = null;

    protected string|Closure|null $panelLayout = 'compact';

    protected string|Closure $removeUploadedFileButtonPosition = 'left';

    protected bool|Closure $shouldAppendFiles = false;

    protected string|Closure $uploadButtonPosition = 'right';

    protected string|Closure $uploadProgressIndicatorPosition = 'right';

    /**
     * @var array<string, string>|Closure
     */
    protected array|Closure $mimeTypeMap = [];

    public function appendFiles(bool|Closure $condition = true): static
    {
        $this->shouldAppendFiles = $condition;

        return $this;
    }

    /**
     * Filament's preset makes twelve calls, seven of them into the deferred crop and resize
     * plugins. What survives is the circle panel, the four bottom-centred controls and `image()`
     * — deliberately not `imageAspectRatio('1:1')`, which without client-side cropping would
     * refuse every photo that is not already square.
     */
    public function avatar(): static
    {
        $this->isAvatar = true;

        $this->image();
        $this->panelLayout('compact circle');
        $this->loadingIndicatorPosition('center bottom');
        $this->removeUploadedFileButtonPosition('center bottom');
        $this->uploadButtonPosition('center bottom');
        $this->uploadProgressIndicatorPosition('center bottom');

        return $this;
    }

    public function image(): static
    {
        $this->acceptedFileTypes(['image/*']);

        return $this;
    }

    public function imagePreviewHeight(string|Closure|null $height): static
    {
        $this->imagePreviewHeight = $height;

        return $this;
    }

    public function itemPanelAspectRatio(string|int|float|Closure|null $ratio): static
    {
        $this->itemPanelAspectRatio = $ratio;

        return $this;
    }

    public function loadingIndicatorPosition(string|Closure $position): static
    {
        $this->loadingIndicatorPosition = $position;

        return $this;
    }

    public function panelAspectRatio(string|Closure|null $ratio): static
    {
        $this->panelAspectRatio = $ratio;

        return $this;
    }

    public function panelLayout(string|Closure|null $layout): static
    {
        $this->panelLayout = $layout;

        return $this;
    }

    public function removeUploadedFileButtonPosition(string|Closure $position): static
    {
        $this->removeUploadedFileButtonPosition = $position;

        return $this;
    }

    public function uploadButtonPosition(string|Closure $position): static
    {
        $this->uploadButtonPosition = $position;

        return $this;
    }

    public function uploadProgressIndicatorPosition(string|Closure $position): static
    {
        $this->uploadProgressIndicatorPosition = $position;

        return $this;
    }

    /**
     * @param array<string, string>|Closure $map
     */
    public function mimeTypeMap(array|Closure $map): static
    {
        $this->mimeTypeMap = $map;

        return $this;
    }

    public function getImagePreviewHeight(): ?string
    {
        return $this->evaluate($this->imagePreviewHeight);
    }

    public function getItemPanelAspectRatio(): int|float|null
    {
        $ratio = $this->evaluate($this->itemPanelAspectRatio);

        if (($this->getPanelLayout() === 'grid') && ! $ratio) {
            return 1;
        }

        if (is_string($ratio)) {
            return $this->calculateAspectRatio($this->normalizeAspectRatio($ratio));
        }

        return $ratio;
    }

    public function getLoadingIndicatorPosition(): string
    {
        return $this->evaluate($this->loadingIndicatorPosition);
    }

    public function getPanelAspectRatio(): ?string
    {
        return $this->normalizeAspectRatio($this->evaluate($this->panelAspectRatio));
    }

    public function getPanelLayout(): ?string
    {
        return $this->evaluate($this->panelLayout);
    }

    public function getRemoveUploadedFileButtonPosition(): string
    {
        return $this->evaluate($this->removeUploadedFileButtonPosition);
    }

    public function getUploadButtonPosition(): string
    {
        return $this->evaluate($this->uploadButtonPosition);
    }

    public function getUploadProgressIndicatorPosition(): string
    {
        return $this->evaluate($this->uploadProgressIndicatorPosition);
    }

    public function isAvatar(): bool
    {
        return (bool) $this->evaluate($this->isAvatar);
    }

    public function shouldAppendFiles(): bool
    {
        return (bool) $this->evaluate($this->shouldAppendFiles);
    }

    /**
     * @return array<string, string>
     */
    public function getMimeTypeMap(): array
    {
        return $this->evaluate($this->mimeTypeMap);
    }

    /**
     * The six values the template cannot read off a single getter. The three maps are cast to an
     * object so an empty one serialises as `{}` rather than `[]`: `StimulusAttributes` JSON-encodes
     * a non-scalar value, Stimulus's `Object` reader throws on an array, and the controller's
     * `Object` values want one shape whether or not the field holds a file.
     *
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        return [
            'uploadedFiles' => (object) ($this->getUploadedFiles() ?? []),
            'stateMap' => (object) wrap($this->getRawState()),
            'mimeTypeMap' => (object) $this->getMimeTypeMap(),
            'maxFileSize' => min(
                $this->getMaxSize() !== null ? $this->getMaxSize() * 1024 : PHP_INT_MAX,
                $this->getMaxUploadSize(),
            ),
            'minSize' => filled($minSize = $this->getMinSize()) ? $minSize . 'KB' : null,
            'maxFilesMessage' => filled($maxFiles = $this->getMaxFiles())
                ? sprintf('The %s field must not have more than %d items.', $this->getLabel(), $maxFiles)
                : null,
        ];
    }
}

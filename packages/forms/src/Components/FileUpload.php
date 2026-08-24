<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use InvalidArgumentException;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Support\Components\Concerns\HasAlignment;

use function Sylament\Support\blank;
use function Sylament\Support\filled;
use function Sylament\Support\wrap;

/**
 * The shipped file field: {@see BaseFileUpload}'s lifecycle behind a FilePond control, whose whole
 * configuration travels to the browser as Stimulus values on the partial's root.
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

    protected bool|Closure $shouldAutomaticallyCropImagesToAspectRatio = false;

    protected string|Closure|null $automaticallyResizeImagesMode = null;

    protected string|Closure|null $automaticallyResizeImagesHeight = null;

    protected string|Closure|null $automaticallyResizeImagesWidth = null;

    protected bool|Closure $shouldAutomaticallyUpscaleImagesWhenResizing = true;

    protected bool|Closure $shouldOrientImagesFromExif = true;

    protected bool|Closure $hasImageEditor = false;

    protected bool|Closure $hasCircleCropper = false;

    protected bool|Closure $canEditSvgs = true;

    protected bool|Closure $isSvgEditingConfirmed = false;

    protected bool|Closure $shouldAutomaticallyOpenImageEditorForAspectRatio = false;

    protected int|Closure|null $imageEditorViewportWidth = null;

    protected int|Closure|null $imageEditorViewportHeight = null;

    protected int $imageEditorMode = 1;

    protected string|Closure|null $imageEditorEmptyFillColor = null;

    /**
     * @var array<string|null>|Closure
     */
    protected array|Closure $imageEditorAspectRatioOptions = [];

    public function appendFiles(bool|Closure $condition = true): static
    {
        $this->shouldAppendFiles = $condition;

        return $this;
    }

    public function automaticallyCropImagesToAspectRatio(bool|Closure $condition = true): static
    {
        $this->shouldAutomaticallyCropImagesToAspectRatio = $condition;

        return $this;
    }

    public function automaticallyResizeImagesMode(string|Closure|null $mode): static
    {
        $this->automaticallyResizeImagesMode = $mode;

        return $this;
    }

    public function automaticallyResizeImagesToHeight(string|Closure|null $height): static
    {
        $this->automaticallyResizeImagesHeight = $height;

        return $this;
    }

    public function automaticallyResizeImagesToWidth(string|Closure|null $width): static
    {
        $this->automaticallyResizeImagesWidth = $width;

        return $this;
    }

    public function automaticallyUpscaleImagesWhenResizing(bool|Closure $condition = true): static
    {
        $this->shouldAutomaticallyUpscaleImagesWhenResizing = $condition;

        return $this;
    }

    public function avatar(): static
    {
        $this->isAvatar = true;

        $this->image();
        $this->imageAspectRatio('1:1');
        $this->automaticallyResizeImagesMode('cover');
        $this->automaticallyUpscaleImagesWhenResizing(false);
        $this->automaticallyCropImagesToAspectRatio();
        $this->automaticallyResizeImagesToHeight('500');
        $this->automaticallyResizeImagesToWidth('500');
        $this->loadingIndicatorPosition('center bottom');
        $this->panelLayout('compact circle');
        $this->removeUploadedFileButtonPosition(fn (FileUpload $component): string => $component->hasImageEditor() ? 'left bottom' : 'center bottom');
        $this->uploadButtonPosition(fn (FileUpload $component): string => $component->hasImageEditor() ? 'right bottom' : 'center bottom');
        $this->uploadProgressIndicatorPosition(fn (FileUpload $component): string => $component->hasImageEditor() ? 'right bottom' : 'center bottom');

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

    public function orientImagesFromExif(bool|Closure $condition = true): static
    {
        $this->shouldOrientImagesFromExif = $condition;

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

    public function imageEditor(bool|Closure $condition = true): static
    {
        $this->hasImageEditor = $condition;

        return $this;
    }

    public function circleCropper(bool|Closure $condition = true): static
    {
        $this->hasCircleCropper = $condition;

        return $this;
    }

    public function editableSvgs(bool|Closure $condition = true): static
    {
        $this->canEditSvgs = $condition;

        return $this;
    }

    public function confirmSvgEditing(bool|Closure $condition = true): static
    {
        $this->isSvgEditingConfirmed = $condition;

        return $this;
    }

    public function automaticallyOpenImageEditorForAspectRatio(bool|Closure $condition = true): static
    {
        $this->shouldAutomaticallyOpenImageEditorForAspectRatio = $condition;

        return $this;
    }

    public function imageEditorViewportWidth(int|Closure|null $width): static
    {
        $this->imageEditorViewportWidth = $width;

        return $this;
    }

    public function imageEditorViewportHeight(int|Closure|null $height): static
    {
        $this->imageEditorViewportHeight = $height;

        return $this;
    }

    public function imageEditorMode(int $mode): static
    {
        if (! in_array($mode, [1, 2, 3], true)) {
            throw new InvalidArgumentException("The file upload editor mode must be either 1, 2 or 3. [{$mode}] given, which is unsupported. See https://github.com/fengyuanchen/cropperjs/blob/v1/README.md#viewmode for more information on the available modes. Mode 0 is not supported, as it does not allow configuration via manual inputs.");
        }

        $this->imageEditorMode = $mode;

        return $this;
    }

    public function imageEditorEmptyFillColor(string|Closure|null $color): static
    {
        $this->imageEditorEmptyFillColor = $color;

        return $this;
    }

    /**
     * @param array<string|null>|Closure $ratios
     */
    public function imageEditorAspectRatioOptions(array|Closure $ratios): static
    {
        $this->imageEditorAspectRatioOptions = $ratios;

        return $this;
    }

    public function shouldAutomaticallyCropImagesToAspectRatio(): bool
    {
        return (bool) $this->evaluate($this->shouldAutomaticallyCropImagesToAspectRatio);
    }

    public function getAutomaticallyCropImagesAspectRatio(): ?string
    {
        if (! $this->shouldAutomaticallyCropImagesToAspectRatio()) {
            return null;
        }

        $imageAspectRatio = $this->getImageAspectRatio();

        if (blank($imageAspectRatio)) {
            return null;
        }

        if (is_array($imageAspectRatio)) {
            $imageAspectRatio = $imageAspectRatio[0] ?? null;
        }

        return $this->normalizeAspectRatio($imageAspectRatio);
    }

    public function getAutomaticallyResizeImagesMode(): ?string
    {
        return $this->evaluate($this->automaticallyResizeImagesMode);
    }

    public function getAutomaticallyResizeImagesHeight(): ?string
    {
        return $this->evaluate($this->automaticallyResizeImagesHeight);
    }

    public function getAutomaticallyResizeImagesWidth(): ?string
    {
        return $this->evaluate($this->automaticallyResizeImagesWidth);
    }

    public function shouldAutomaticallyUpscaleImagesWhenResizing(): bool
    {
        return (bool) $this->evaluate($this->shouldAutomaticallyUpscaleImagesWhenResizing);
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

    public function shouldOrientImagesFromExif(): bool
    {
        return (bool) $this->evaluate($this->shouldOrientImagesFromExif);
    }

    /**
     * @return array<string, string>
     */
    public function getMimeTypeMap(): array
    {
        return $this->evaluate($this->mimeTypeMap);
    }

    public function getImageEditorViewportHeight(): ?int
    {
        if (($targetHeight = (int) $this->getAutomaticallyResizeImagesHeight()) > 1) {
            return (int) round($targetHeight * $this->getParentTargetSizes($targetHeight));
        }

        if (filled($ratio = $this->getAutomaticallyCropImagesAspectRatio())) {
            $parts = explode(':', $ratio);

            if (count($parts) === 2) {
                return (int) $parts[1];
            }
        }

        return $this->evaluate($this->imageEditorViewportHeight);
    }

    public function getImageEditorViewportWidth(): ?int
    {
        if (($targetWidth = (int) $this->getAutomaticallyResizeImagesWidth()) > 1) {
            return (int) round($targetWidth * $this->getParentTargetSizes($targetWidth));
        }

        if (filled($ratio = $this->getAutomaticallyCropImagesAspectRatio())) {
            $parts = explode(':', $ratio);

            if (count($parts) === 2) {
                return (int) $parts[0];
            }
        }

        return $this->evaluate($this->imageEditorViewportWidth);
    }

    protected function getParentTargetSizes(int $widthOrHeight): int|float
    {
        $targetWidth = (int) $this->getAutomaticallyResizeImagesWidth();

        if ($targetWidth === 0) {
            return 1;
        }

        return $widthOrHeight > 1 ? 360 / $targetWidth : 1;
    }

    public function getImageEditorMode(): int
    {
        return $this->imageEditorMode;
    }

    public function getImageEditorEmptyFillColor(): ?string
    {
        return $this->evaluate($this->imageEditorEmptyFillColor);
    }

    public function hasImageEditor(): bool
    {
        if ($this->shouldAutomaticallyOpenImageEditorForAspectRatio()) {
            return true;
        }

        return (bool) $this->evaluate($this->hasImageEditor);
    }

    public function isImageEditorExplicitlyEnabled(): bool
    {
        return (bool) $this->evaluate($this->hasImageEditor);
    }

    public function hasCircleCropper(): bool
    {
        return (bool) $this->evaluate($this->hasCircleCropper);
    }

    public function canEditSvgs(): bool
    {
        return (bool) $this->evaluate($this->canEditSvgs);
    }

    public function isSvgEditingConfirmed(): bool
    {
        return (bool) $this->evaluate($this->isSvgEditingConfirmed);
    }

    public function shouldAutomaticallyOpenImageEditorForAspectRatio(): bool
    {
        if (! $this->evaluate($this->shouldAutomaticallyOpenImageEditorForAspectRatio)) {
            return false;
        }

        if ($this->isMultiple()) {
            throw new InvalidArgumentException('The [automaticallyOpenImageEditorForAspectRatio()] method cannot be used when [multiple()] is enabled.');
        }

        $ratio = $this->getImageAspectRatio();

        if (blank($ratio)) {
            throw new InvalidArgumentException('The [automaticallyOpenImageEditorForAspectRatio()] method requires [imageAspectRatio()] to be set with a single aspect ratio.');
        }

        if (is_array($ratio) && count($ratio) > 1) {
            throw new InvalidArgumentException('The [automaticallyOpenImageEditorForAspectRatio()] method cannot be used when [imageAspectRatio()] has multiple allowed aspect ratios.');
        }

        return true;
    }

    public function getAutomaticallyOpenImageEditorForAspectRatio(): ?float
    {
        if (! $this->shouldAutomaticallyOpenImageEditorForAspectRatio()) {
            return null;
        }

        $ratio = $this->getImageAspectRatio();

        if (is_array($ratio)) {
            $ratio = $ratio[0] ?? null;
        }

        if (blank($ratio)) {
            return null;
        }

        return $this->calculateAspectRatio($ratio);
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

<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Concerns;

use Closure;
use Sylament\Support\Enums\FontFamily;

trait HasFontFamily
{
    protected FontFamily|string|Closure|null $fontFamily = null;

    public function fontFamily(FontFamily|string|Closure|null $family): static
    {
        $this->fontFamily = $family;

        return $this;
    }

    public function getFontFamily(): FontFamily|string|null
    {
        $family = $this->evaluate($this->fontFamily);

        if (is_string($family)) {
            return FontFamily::tryFrom($family) ?? $family;
        }

        return $family;
    }

    /**
     * The class fragment templates emit for the font family: `sy-font-<value>` for
     * the enum cases, a custom string as-is, null when unset — so templates never
     * type-sniff the enum-vs-string union.
     */
    public function getFontFamilyClass(): ?string
    {
        $fontFamily = $this->getFontFamily();

        if ($fontFamily instanceof FontFamily) {
            return 'sy-font-' . $fontFamily->value;
        }

        return $fontFamily;
    }

    public function hasFontFamily(): bool
    {
        return $this->fontFamily !== null;
    }
}

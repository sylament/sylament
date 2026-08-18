<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Contracts;

use BackedEnum;
use Sylament\Schemas\Components\Contracts\HasAffixActions;
use Twig\Markup;

interface HasAffixes extends HasAffixActions
{
    public function getPrefixLabel(): string|Markup|null;

    public function getPrefixIcon(): string|BackedEnum|Markup|null;

    /**
     * @return string|array<string>|null
     */
    public function getPrefixIconColor(): string|array|null;

    public function getSuffixLabel(): string|Markup|null;

    public function getSuffixIcon(): string|BackedEnum|Markup|null;

    /**
     * @return string|array<string>|null
     */
    public function getSuffixIconColor(): string|array|null;

    public function isPrefixInline(): bool;

    public function isSuffixInline(): bool;
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Pages;

use BackedEnum;

abstract class Page
{
    protected static ?string $title = null;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $navigationGroup = null;

    protected ?string $heading = null;

    protected ?string $subheading = null;

    public function getTitle(): ?string
    {
        return static::$title;
    }

    public function getHeading(): ?string
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return $this->subheading;
    }

    public static function getNavigationLabel(): ?string
    {
        return static::$navigationLabel;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return static::$navigationIcon;
    }

    public static function getNavigationSort(): ?int
    {
        return static::$navigationSort;
    }

    public static function getNavigationGroup(): ?string
    {
        return static::$navigationGroup;
    }
}

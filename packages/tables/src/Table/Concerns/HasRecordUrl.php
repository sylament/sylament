<?php

declare(strict_types=1);

namespace Sylament\Tables\Table\Concerns;

use Closure;
use Sylament\Support\Twig\ComponentAttributeBag;

use function Sylament\Support\sanitize_url;

trait HasRecordUrl
{
    protected bool|Closure $shouldOpenRecordUrlInNewTab = false;

    protected string|Closure|null $recordUrl = null;

    protected bool $hasCustomRecordUrl = false;

    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $extraRecordLinkAttributes = [];

    public function openRecordUrlInNewTab(bool|Closure $condition = true): static
    {
        $this->shouldOpenRecordUrlInNewTab = $condition;

        return $this;
    }

    /**
     * The second argument is applied only when it is non-null, so a `recordUrl()`
     * call never silently clears an `openRecordUrlInNewTab()` set before it.
     */
    public function recordUrl(string|Closure|null $url, bool|Closure|null $shouldOpenInNewTab = null): static
    {
        $this->recordUrl = $url;
        $this->hasCustomRecordUrl = true;

        if ($shouldOpenInNewTab !== null) {
            $this->openRecordUrlInNewTab($shouldOpenInNewTab);
        }

        return $this;
    }

    /**
     * Whether a `recordUrl()` was set at all — the guard a routed page reads
     * before seeding a default of its own.
     */
    public function hasCustomRecordUrl(): bool
    {
        return $this->hasCustomRecordUrl;
    }

    /**
     * @param object|array<string, mixed> $record
     */
    public function getRecordUrl(object|array $record): ?string
    {
        return sanitize_url($this->evaluate(
            $this->recordUrl,
            ['record' => $record],
            is_object($record) ? [$record::class => $record] : [],
        ));
    }

    /**
     * @param object|array<string, mixed> $record
     */
    public function shouldOpenRecordUrlInNewTab(object|array $record): bool
    {
        return (bool) $this->evaluate(
            $this->shouldOpenRecordUrlInNewTab,
            ['record' => $record],
            is_object($record) ? [$record::class => $record] : [],
        );
    }

    /**
     * Security: the values are rendered unescaped into the record anchor, so a
     * user-derived attribute must be sanitized before it is handed over.
     *
     * @param array<mixed>|Closure $attributes
     */
    public function extraRecordLinkAttributes(array|Closure $attributes, bool $merge = false): static
    {
        if ($merge) {
            $this->extraRecordLinkAttributes[] = $attributes;
        } else {
            $this->extraRecordLinkAttributes = [$attributes];
        }

        return $this;
    }

    /**
     * @param object|array<string, mixed> $record
     * @return array<string, mixed>
     */
    public function getExtraRecordLinkAttributes(object|array $record): array
    {
        $attributeBag = new ComponentAttributeBag();

        foreach ($this->extraRecordLinkAttributes as $extraAttributes) {
            $attributeBag = $attributeBag->merge($this->evaluate(
                $extraAttributes,
                ['record' => $record],
                is_object($record) ? [$record::class => $record] : [],
            ) ?: []);
        }

        return $attributeBag->getAttributes();
    }
}

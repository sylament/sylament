<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Twig\Markup;

trait CanBeSearchable
{
    protected bool|Closure|null $isSearchable = null;

    protected string|Markup|Closure|null $noOptionsMessage = null;

    protected string|Markup|Closure|null $noSearchResultsMessage = null;

    protected int|Closure $searchDebounce = 1000;

    protected string|Closure|null $searchingMessage = null;

    protected string|Markup|Closure|null $searchPrompt = null;

    /**
     * Nullable so `searchLabels(null)` — the setter's own declared arm — assigns
     * instead of throwing a `TypeError` against a `bool|Closure` property, as it
     * does in Filament. Null means off, through {@see shouldSearchLabels()}'s cast.
     */
    protected bool|Closure|null $shouldSearchLabels = true;

    /**
     * Nullable so `searchValues(null)` — the setter's own declared arm — assigns
     * instead of throwing a `TypeError` against a `bool|Closure` property, as it
     * does in Filament. Null means off, through {@see shouldSearchValues()}'s cast.
     */
    protected bool|Closure|null $shouldSearchValues = false;

    public function searchable(bool|Closure|null $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function noOptionsMessage(string|Markup|Closure|null $message): static
    {
        $this->noOptionsMessage = $message;

        return $this;
    }

    public function noSearchResultsMessage(string|Markup|Closure|null $message): static
    {
        $this->noSearchResultsMessage = $message;

        return $this;
    }

    public function searchDebounce(int|Closure $debounce): static
    {
        $this->searchDebounce = $debounce;

        return $this;
    }

    public function searchingMessage(string|Closure|null $message): static
    {
        $this->searchingMessage = $message;

        return $this;
    }

    public function searchPrompt(string|Markup|Closure|null $message): static
    {
        $this->searchPrompt = $message;

        return $this;
    }

    public function searchLabels(bool|Closure|null $condition = true): static
    {
        $this->shouldSearchLabels = $condition;

        return $this;
    }

    public function searchValues(bool|Closure|null $condition = true): static
    {
        $this->shouldSearchValues = $condition;

        return $this;
    }

    public function getNoOptionsMessage(): string|Markup
    {
        return $this->evaluate($this->noOptionsMessage) ?? 'No options available.';
    }

    public function getNoSearchResultsMessage(): string|Markup
    {
        return $this->evaluate($this->noSearchResultsMessage) ?? 'No options match your search.';
    }

    public function getSearchPrompt(): string|Markup
    {
        return $this->evaluate($this->searchPrompt) ?? 'Start typing to search...';
    }

    public function shouldSearchLabels(): bool
    {
        return (bool) $this->evaluate($this->shouldSearchLabels);
    }

    public function shouldSearchValues(): bool
    {
        return (bool) $this->evaluate($this->shouldSearchValues);
    }

    /**
     * @return list<string>
     */
    public function getSearchableOptionFields(): array
    {
        return [
            ...($this->shouldSearchLabels() ? ['label'] : []),
            ...($this->shouldSearchValues() ? ['value'] : []),
        ];
    }

    public function getSearchDebounce(): int
    {
        return $this->evaluate($this->searchDebounce);
    }

    public function getSearchingMessage(): string
    {
        return $this->evaluate($this->searchingMessage) ?? 'Searching...';
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }
}

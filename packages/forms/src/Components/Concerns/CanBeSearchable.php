<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Twig\Markup;

trait CanBeSearchable
{
    protected bool|Closure|null $isSearchable = null;

    protected string|Markup|TranslatableInterface|Closure|null $noOptionsMessage = null;

    protected string|Markup|TranslatableInterface|Closure|null $noSearchResultsMessage = null;

    protected int|Closure $searchDebounce = 1000;

    protected string|TranslatableInterface|Closure|null $searchingMessage = null;

    protected string|Markup|TranslatableInterface|Closure|null $searchPrompt = null;

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

    public function noOptionsMessage(string|Markup|TranslatableInterface|Closure|null $message): static
    {
        $this->noOptionsMessage = $message;

        return $this;
    }

    public function noSearchResultsMessage(string|Markup|TranslatableInterface|Closure|null $message): static
    {
        $this->noSearchResultsMessage = $message;

        return $this;
    }

    public function searchDebounce(int|Closure $debounce): static
    {
        $this->searchDebounce = $debounce;

        return $this;
    }

    public function searchingMessage(string|TranslatableInterface|Closure|null $message): static
    {
        $this->searchingMessage = $message;

        return $this;
    }

    public function searchPrompt(string|Markup|TranslatableInterface|Closure|null $message): static
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
        return $this->translateText($this->evaluate($this->noOptionsMessage)) ?? new TranslatableMessage('components.select.no_options_message', domain: 'sylament-forms')->trans($this->getTranslator());
    }

    public function getNoSearchResultsMessage(): string|Markup
    {
        return $this->translateText($this->evaluate($this->noSearchResultsMessage)) ?? new TranslatableMessage('components.select.no_search_results_message', domain: 'sylament-forms')->trans($this->getTranslator());
    }

    public function getSearchPrompt(): string|Markup
    {
        return $this->translateText($this->evaluate($this->searchPrompt)) ?? new TranslatableMessage('components.select.search_prompt', domain: 'sylament-forms')->trans($this->getTranslator());
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
        return (string) $this->translateText($this->evaluate($this->searchingMessage) ?? new TranslatableMessage('components.select.searching_message', domain: 'sylament-forms'));
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }
}

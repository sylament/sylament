<?php

declare(strict_types=1);

namespace Sylament\Panels\GlobalSearch;

class GlobalSearchResults
{
    /**
     * @var array<string, array<GlobalSearchResult>>
     */
    protected array $categories = [];

    final public function __construct()
    {
    }

    public static function make(): static
    {
        return new static();
    }

    /**
     * @param iterable<GlobalSearchResult> $results
     */
    public function category(string $name, iterable $results = []): static
    {
        $this->categories[$name] = is_array($results) ? $results : iterator_to_array($results);

        return $this;
    }

    /**
     * @return array<string, array<GlobalSearchResult>>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }
}

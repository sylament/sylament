<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

trait HasComponents
{
    /**
     * @var array<class-string>
     */
    protected array $pages = [];

    /**
     * @param array<class-string> $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = [...$this->pages, ...$pages];

        return $this;
    }

    /**
     * @return array<class-string>
     */
    public function getPages(): array
    {
        return array_values(array_unique($this->pages));
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Sylament\Panels\Pages\Page;

trait HasComponents
{
    /**
     * @var array<class-string<Page>>
     */
    protected array $pages = [];

    /**
     * @param array<class-string<Page>> $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = [...$this->pages, ...$pages];

        return $this;
    }

    /**
     * @return array<class-string<Page>>
     */
    public function getPages(): array
    {
        return array_values(array_unique($this->pages));
    }
}

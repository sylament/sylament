<?php

declare(strict_types=1);

namespace Sylament\Panels\Panel\Concerns;

use Sylament\Panels\Pages\Page;
use Sylament\Panels\Resources\Resource;

trait HasComponents
{
    /**
     * @var array<class-string<Page>>
     */
    protected array $pages = [];

    /**
     * @var array<class-string<Resource>>
     */
    protected array $resources = [];

    /**
     * @param array<class-string<Page>> $pages
     */
    public function pages(array $pages): static
    {
        $this->pages = [...$this->pages, ...$pages];

        return $this;
    }

    /**
     * @param array<class-string<Resource>> $resources
     */
    public function resources(array $resources): static
    {
        $this->resources = [...$this->resources, ...$resources];

        return $this;
    }

    /**
     * @return array<class-string<Page>>
     */
    public function getPages(): array
    {
        return array_values(array_unique($this->pages));
    }

    /**
     * @return array<class-string<Resource>>
     */
    public function getResources(): array
    {
        return array_values(array_unique($this->resources));
    }
}

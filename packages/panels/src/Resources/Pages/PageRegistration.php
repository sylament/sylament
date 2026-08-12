<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Sylament\Panels\Pages\Page;

final readonly class PageRegistration
{
    /**
     * @param class-string<Page> $page
     * @param string             $path relative to the resource's own route prefix
     */
    public function __construct(
        public string $page,
        public string $path,
    ) {
    }
}

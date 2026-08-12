<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Sylament\Panels\Pages\Page;

final readonly class PageRegistration
{
    /**
     * @param class-string<Page> $page
     * @param string             $path relative to the resource's own route prefix; the leading slash is optional,
     *                                 since the route loader joins the two halves with exactly one separator either way
     */
    public function __construct(
        public string $page,
        public string $path,
    ) {
    }
}

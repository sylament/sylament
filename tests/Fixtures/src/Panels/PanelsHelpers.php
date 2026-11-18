<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels;

use LogicException;
use Sylament\Tests\Fixtures\Tables\Selectors as TablesSelectors;
use Symfony\Component\DomCrawler\Crawler;

trait PanelsHelpers
{
    /**
     * The props a live page rendered, decoded from its own root's `data-live-props-value`, in a
     * visited document or the page's own render alike: never another live root's on the page,
     * such as the topbar's global search, which a document renders first.
     *
     * @return array<string, mixed>
     */
    protected static function renderedLiveProps(Crawler $page): array
    {
        $root = $page->filter(Selectors::PAGE . TablesSelectors::LIVE_PROPS . ', ' . Selectors::SIMPLE_PAGE . TablesSelectors::LIVE_PROPS);

        if ($root->count() === 0) {
            throw new LogicException('The markup holds no live page root.');
        }

        $props = json_decode((string) $root->attr('data-live-props-value'), true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($props)) {
            throw new LogicException("The page's live props are not an object.");
        }

        /** @var array<string, mixed> $props */
        return $props;
    }
}

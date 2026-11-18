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

    /**
     * The three values the unsaved-changes guard hands its controller (`savedHash`, `dataModel`, `guardActions`), read off
     * the one guard a page rendered, or null when it rendered none.
     *
     * @return array{savedHash: ?string, dataModel: ?string, guardActions: ?string}|null
     */
    protected static function unsavedChangesGuard(Crawler $page): ?array
    {
        $guard = $page->filter(Selectors::UNSAVED_CHANGES_GUARD);

        if ($guard->count() > 1) {
            throw new LogicException('The markup holds more than one unsaved-changes guard.');
        }

        if ($guard->count() === 0) {
            return null;
        }

        return [
            'savedHash' => $guard->attr('data-sylament--panels--unsaved-changes-saved-hash-value'),
            'dataModel' => $guard->attr('data-sylament--panels--unsaved-changes-data-model-value'),
            'guardActions' => $guard->attr('data-sylament--panels--unsaved-changes-guard-actions-value'),
        ];
    }

    /**
     * The categories a global search rendered, in order, each heading keyed to the titles of its results in order.
     *
     * @return array<string, list<string>>
     */
    protected static function globalSearchCategories(Crawler $search): array
    {
        $categories = [];

        foreach ($search->filter(Selectors::GLOBAL_SEARCH_RESULT_GROUP) as $group) {
            $group = new Crawler($group);
            $categories[$group->filter(Selectors::GLOBAL_SEARCH_RESULT_GROUP_HEADING)->text()] = $group->filter(Selectors::GLOBAL_SEARCH_RESULT_HEADING)->each(fn (Crawler $heading): string => $heading->text());
        }

        return $categories;
    }
}

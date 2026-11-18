<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

/**
 * The {@see DefaultedJarFilterTable} binding its applied filters to the page's `filters`
 * query parameter.
 */
#[AsLiveComponent(name: 'Tables:QueryStringJarFilterTable', template: '@Fixtures/tables/table.html.twig')]
class QueryStringJarFilterTable extends DefaultedJarFilterTable
{
    protected function getTableUrlMapping(string $prop): ?UrlMapping
    {
        return $prop === 'filters' ? new UrlMapping(as: 'filters') : null;
    }
}

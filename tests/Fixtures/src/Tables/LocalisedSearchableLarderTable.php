<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see SearchableLarderTable} answering on the localised live route, so a request carries the locale the
 * tester sets and the catalogue words its chips in it.
 */
#[AsLiveComponent(name: 'Tables:LocalisedSearchableLarderTable', template: '@Fixtures/tables/table.html.twig', route: 'sylament_tests_tables_localised_live')]
class LocalisedSearchableLarderTable extends SearchableLarderTable
{
}

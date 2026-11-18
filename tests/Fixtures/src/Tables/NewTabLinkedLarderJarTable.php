<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * The {@see LinkedLarderJarTable} linking each jar again through `recordUrl()`, whose second
 * argument opens the link in a new tab.
 */
#[AsLiveComponent(name: 'Tables:NewTabLinkedLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class NewTabLinkedLarderJarTable extends LinkedLarderJarTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)->recordUrl(static fn (LarderJar $record): string => "/jars/{$record->id}", true);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Pages\Page;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Larder;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ListStockedLarders;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderBins;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderFlatJars;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJars;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderJarShelf;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\ManageLarderViewableJars;

/**
 * A larder titled by its name whose record pages are its managed listings, three of them in the
 * record's sub-navigation band.
 */
final class StockedLarderResource extends Resource
{
    protected static ?string $recordClass = Larder::class;

    protected static ?string $recordTitleAttribute = 'name';

    public function getPages(): array
    {
        return [
            'index' => ListStockedLarders::route('/'),
            'jars' => ManageLarderJars::route('/{record}/jars'),
            'shelf' => ManageLarderJarShelf::route('/{record}/shelf'),
            'bins' => ManageLarderBins::route('/{record}/bins'),
            'flat-jars' => ManageLarderFlatJars::route('/{record}/flat-jars'),
            'viewable-jars' => ManageLarderViewableJars::route('/{record}/viewable-jars'),
        ];
    }

    public function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([ManageLarderJars::class, ManageLarderJarShelf::class, ManageLarderBins::class]);
    }
}

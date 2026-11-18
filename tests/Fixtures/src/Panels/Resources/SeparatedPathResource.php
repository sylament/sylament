<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\OrderLine;
use Sylament\Tests\Fixtures\Panels\Resources\Pages\PreviewSeparatedPath;

/**
 * A resource whose one page is mounted where a slash, a separator, or a second placeholder
 * follows the record placeholder; it has no index, so it lists nothing in navigation.
 */
final class SeparatedPathResource extends Resource
{
    protected static ?string $recordClass = OrderLine::class;

    protected static ?string $slug = 'previews';

    public function getPages(): array
    {
        return [
            'edit' => PreviewSeparatedPath::route('/{record}/edit'),
            'dashed' => PreviewSeparatedPath::route('/{record}-preview'),
            'tilded' => PreviewSeparatedPath::route('/{record}~preview'),
            'dotted' => PreviewSeparatedPath::route('/{record}.pdf'),
            'localised' => PreviewSeparatedPath::route('/{record}-{locale}'),
        ];
    }
}

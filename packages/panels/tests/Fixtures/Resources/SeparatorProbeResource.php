<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Catalogue;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\SeparatorProbePage;

/**
 * One page class at six record paths: the five characters the route compiler
 * treats as separators and can meet a key, plus the ordinary `/` shape as the
 * control. It registers no bare `/{record}`, which would otherwise match
 * `/5-preview` whole and shadow the sibling registered after it.
 */
final class SeparatorProbeResource extends Resource
{
    protected static ?string $recordClass = Catalogue::class;

    protected static ?string $pluralRecordLabel = 'separator probes';

    protected static bool $isGloballySearchable = false;

    public function getPages(): array
    {
        return [
            'index' => new PageRegistration(SeparatorProbePage::class, '/'),
            'probe' => new PageRegistration(SeparatorProbePage::class, '/{record}/probe'),
            'dash' => new PageRegistration(SeparatorProbePage::class, '/{record}-preview'),
            'tilde' => new PageRegistration(SeparatorProbePage::class, '/{record}~x'),
            'underscore' => new PageRegistration(SeparatorProbePage::class, '/{record}_x'),
            'equals' => new PageRegistration(SeparatorProbePage::class, '/{record}=x'),
            'dot' => new PageRegistration(SeparatorProbePage::class, '/{record}.pdf'),
        ];
    }
}

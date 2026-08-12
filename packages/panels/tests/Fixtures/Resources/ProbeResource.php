<?php

declare(strict_types=1);

namespace Sylament\Panels\Tests\Fixtures\Resources;

use Sylament\Panels\Resources\Pages\PageRegistration;
use Sylament\Panels\Resources\Resource;
use Sylament\Panels\Tests\Fixtures\Doctrine\Entity\Article;
use Sylament\Panels\Tests\Fixtures\Resources\Pages\RecordProbePage;

final class ProbeResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    /**
     * The derived slug would be `probes` — slugs come from the resource basename,
     * not the record class — and every route expectation pins `articles`, so this
     * also exercises the explicit-slug arm.
     */
    protected static ?string $slug = 'articles';

    public function getPages(): array
    {
        return [
            'index' => new PageRegistration(RecordProbePage::class, '/'),
            'probe' => new PageRegistration(RecordProbePage::class, '/{record}/probe'),
        ];
    }
}

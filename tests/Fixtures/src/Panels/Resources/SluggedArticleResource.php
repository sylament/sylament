<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources;

use Closure;
use LogicException;
use Sylament\Panels\Resources\Resource;
use Sylament\Tests\Fixtures\Entity\Article;

/**
 * Names as its route key a field the article does not map, and keys and resolves its records itself.
 */
final class SluggedArticleResource extends Resource
{
    protected static ?string $recordClass = Article::class;

    protected static ?string $recordRouteKeyName = 'slug';

    public function getRecordRouteKey(object $record): string
    {
        return $record instanceof Article ? "article-{$record->id}" : throw new LogicException('Only an article has a slug.');
    }

    public function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?object
    {
        $record = $this->getRecordQuery()
            ->andWhere('record.id = :id')
            ->setParameter('id', (int) mb_substr((string) $key, mb_strlen('article-')))
            ->getQuery()
            ->getOneOrNullResult();

        return is_object($record) ? $record : null;
    }
}

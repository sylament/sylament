<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\QueryBuilder;
use Sylament\Tables\Columns\Summarizers\Summarizer;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\Component\DomCrawler\Crawler;

trait TablesHelpers
{
    /**
     * The summarizer's body as a summary cell renders it: summarizing a text column of a
     * table, pointed at `$query`, every larder jar by default.
     */
    protected function renderSummarizer(Summarizer $summarizer, string $column = 'position', ?QueryBuilder $query = null): Crawler
    {
        TextColumn::make($column)->table(Table::make(new SummarizerMountHost()))->summarize($summarizer);

        return $this->renderViewComponent($summarizer->query($query ?? $this->entityManager()->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar')));
    }

    /**
     * Each text summarizer's body in the markup as its label and its figure (`Sum 7`), in the
     * order they render.
     *
     * @return list<string>
     */
    protected static function textSummaries(Crawler $crawler): array
    {
        return $crawler->filter(Selectors::summarizer('text'))->each(static fn (Crawler $body): string => mb_trim($body->filter(Selectors::summarizerLabel('text'))->text('') . ' ' . $body->filter(Selectors::summarizerFigure('text'))->text('')));
    }
}

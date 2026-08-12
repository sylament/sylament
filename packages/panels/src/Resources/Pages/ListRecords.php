<?php

declare(strict_types=1);

namespace Sylament\Panels\Resources\Pages;

use Doctrine\ORM\QueryBuilder;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Twig\Markup;

abstract class ListRecords extends Page implements InteractsWithSchemas, InteractsWithTable
{
    use HasSchemas;
    use HasTable;

    protected ?string $template = '@SylamentPanels/partials/ListRecords/index.html.twig';

    public function table(Table $table): Table
    {
        $resource = $this->getResourceInstance();

        return $resource->table($table->query(fn (): QueryBuilder => $resource->getRecordQuery()));
    }

    public function getTitle(): string|Markup
    {
        return static::$title ?? $this->getResourceInstance()->getTitleCasePluralRecordLabel();
    }

    /**
     * The filters form is cache-only and a list page declares no form of its
     * own — so the discovery list is empty rather than the default `schema`,
     * which would demand a `schema()` method on every dehydrate.
     *
     * @return array<string>
     */
    public function schemas(): array
    {
        return [];
    }
}

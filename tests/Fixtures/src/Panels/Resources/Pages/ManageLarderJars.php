<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Doctrine\ORM\QueryBuilder;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Panels\Resources\Pages\ManageRelatedRecords;
use Sylament\Schemas\Components\Tabs\Tab;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Panels\Resources\PreserveJarResource;
use Sylament\Tests\Fixtures\Panels\Resources\StockedLarderResource;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * A larder's jars as the resource nested under it lists them, under an all tab and a waxed tab;
 * its header creates a jar and renames the larder.
 */
#[AsLiveComponent]
final class ManageLarderJars extends ManageRelatedRecords
{
    protected static string $resource = StockedLarderResource::class;

    protected static string $relationship = 'jars';

    protected static ?string $relatedResource = PreserveJarResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'waxed' => Tab::make()->query(static fn (QueryBuilder $query): QueryBuilder => $query
                ->andWhere($query->getRootAliases()[0] . '.seal = :seal')
                ->setParameter('seal', JarSeal::Wax)),
        ];
    }

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            EditAction::make('rename')->schema([TextInput::make('name')]),
        ];
    }
}

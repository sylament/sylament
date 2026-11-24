<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Doctrine\ORM\EntityManagerInterface;
use Sylament\Actions\Action;
use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Entity\JarSeal;
use Sylament\Tests\Fixtures\Entity\LarderJar;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Twig\Markup;

/**
 * A searchable, striped table of larder jars under a heading and a description, its
 * sealed jars' rows carrying a class of their own and a `create` header action
 * beside them; the heading (text or `Markup`), its level, the action and the record labels are mount
 * props.
 */
#[AsLiveComponent(name: 'Tables:HeadedLarderJarTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class HeadedLarderJarTable implements InteractsWithActions, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasTable;

    #[LiveProp]
    public ?string $heading = 'Larder jars';

    #[LiveProp]
    public bool $markupHeading = false;

    #[LiveProp]
    public int $rootHeadingLevel = 2;

    #[LiveProp]
    public bool $creates = true;

    #[LiveProp]
    public ?string $recordLabel = null;

    #[LiveProp]
    public ?string $pluralRecordLabel = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->entityManager->createQueryBuilder()->select('jar')->from(LarderJar::class, 'jar'))
            ->columns([
                TextColumn::make('label')->searchable(),
            ])
            ->defaultSort('label')
            ->recordLabel($this->recordLabel)
            ->pluralRecordLabel($this->pluralRecordLabel)
            ->heading($this->markupHeading && $this->heading !== null ? new Markup($this->heading, 'UTF-8') : $this->heading)
            ->description($this->heading === null ? null : 'The jars on the shelf')
            ->rootHeadingLevel($this->rootHeadingLevel)
            ->striped()
            ->recordClasses(static fn (LarderJar $record): array => ['jar-sealed' => $record->seal instanceof JarSeal])
            ->headerActions($this->creates ? [Action::make('create')] : []);
    }
}

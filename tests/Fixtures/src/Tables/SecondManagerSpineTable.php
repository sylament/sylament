<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Tables;

use Sylament\Actions\Concerns\HasActions;
use Sylament\Actions\Contracts\InteractsWithActions;
use Sylament\Actions\CreateAction;
use Sylament\Actions\EditAction;
use Sylament\Actions\ReplicateAction;
use Sylament\Forms\Components\TextInput;
use Sylament\Schemas\Concerns\HasSchemas;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Schemas\Schema;
use Sylament\Tables\Columns\TextColumn;
use Sylament\Tables\Concerns\HasTable;
use Sylament\Tables\Contracts\InteractsWithTable;
use Sylament\Tables\Table;
use Sylament\Tests\Fixtures\Actions\RecordingAssociationReader;
use Sylament\Tests\Fixtures\Actions\SecondManager;
use Sylament\Tests\Fixtures\Entity\VolumeSpine;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * A table of `VolumeSpine`s whose query runs on the request's {@see SecondManager}, with a
 * `create` header action and `edit` and `replicate` row actions whose schemas hold an
 * application's recording reader.
 */
#[AsLiveComponent(name: 'Tables:SecondManagerSpineTable', template: '@Fixtures/tables/table-with-modals.html.twig')]
class SecondManagerSpineTable implements InteractsWithActions, InteractsWithSchemas, InteractsWithTable
{
    use DefaultActionTrait;
    use HasActions;
    use HasSchemas;
    use HasTable;

    public function __construct(
        private readonly SecondManager $secondManager,
        private readonly RecordingAssociationReader $reader,
    ) {
    }

    public function schemas(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->secondManager->get()->createQueryBuilder()->select('spine')->from(VolumeSpine::class, 'spine'))
            ->columns([
                TextColumn::make('label'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->recordClass(VolumeSpine::class)
                    ->schema($this->watched(...)),
            ])
            ->recordActions([
                EditAction::make()->schema($this->watched(...)),
                ReplicateAction::make()->schema($this->watched(...)),
            ]);
    }

    private function watched(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return $schema->components([TextInput::make('code'), TextInput::make('label')]);
    }
}

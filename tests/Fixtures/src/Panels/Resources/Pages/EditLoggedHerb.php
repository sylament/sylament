<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\EditRecord;
use Sylament\Schemas\Schema;
use Sylament\Tests\Fixtures\Entity\CompanionHerb;
use Sylament\Tests\Fixtures\Panels\KeyReadsRecordingAssociationReader;
use Sylament\Tests\Fixtures\Panels\Resources\VariantHerbResource;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Records every fill and save hook, both mutators and the write in the request's {@see HookLog}, and hands its form an
 * application's reader that records its key reads there too. The fill mutator upper-cases the name and the save mutator
 * capitalises it, adding a `status` no field maps for {@see self::UNMAPPED_NAME}; the write sets the Latin name, which
 * the edit form leaves out, from the name already mapped.
 */
#[AsLiveComponent]
final class EditLoggedHerb extends EditRecord
{
    public const string UNMAPPED_NAME = 'Rue';

    protected static string $resource = VariantHerbResource::class;

    public function __construct(
        private readonly HookLog $hookLog,
        private readonly KeyReadsRecordingAssociationReader $reader,
    ) {
    }

    public function form(Schema $schema): Schema
    {
        $schema->setAssociationReader($this->reader);

        return parent::form($schema);
    }

    protected function beforeFill(): void
    {
        $this->hookLog->record('beforeFill');
    }

    protected function afterFill(): void
    {
        $this->hookLog->record('afterFill');
    }

    protected function beforeValidate(): void
    {
        $this->hookLog->record('beforeValidate');
    }

    protected function afterValidate(): void
    {
        $this->hookLog->record('afterValidate');
    }

    protected function beforeSave(): void
    {
        $this->hookLog->record('beforeSave');
    }

    protected function afterSave(): void
    {
        $this->hookLog->record('afterSave');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->hookLog->record('mutateFormDataBeforeFill');

        $name = $data['name'] ?? null;

        return is_string($name) ? [...$data, 'name' => mb_strtoupper($name)] : $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->hookLog->record('mutateFormDataBeforeSave');

        $name = $data['name'] ?? null;

        if (!is_string($name)) {
            return $data;
        }

        return [
            ...$data,
            'name' => ucfirst(mb_strtolower($name)),
            ...($name === self::UNMAPPED_NAME ? ['status' => 'flagged'] : []),
        ];
    }

    protected function handleRecordUpdate(object $record): object
    {
        $this->hookLog->record('handleRecordUpdate');

        if ($record instanceof CompanionHerb) {
            $record->latinName = "Herba {$record->name}";
        }

        return parent::handleRecordUpdate($record);
    }
}

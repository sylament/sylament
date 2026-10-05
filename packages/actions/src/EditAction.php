<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;
use Sylament\Actions\Concerns\CanMutateRecordData;
use Sylament\Actions\Concerns\RefusesUnmappedData;
use Sylament\Schemas\Schema;

class EditAction extends Action
{
    use CanCustomizeProcess;
    use CanMutateRecordData;
    use RefusesUnmappedData;

    public static function getDefaultName(): ?string
    {
        return 'edit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Edit');

        $this->modalHeading(fn (): string => ($title = $this->getRecordTitle()) === null ? 'Edit' : "Edit {$title}");

        $this->modalSubmitActionLabel('Save changes');

        $this->successNotificationTitle('Saved');

        $this->defaultColor('primary');

        $this->icon('heroicons:pencil-square-20-solid');

        $this->fillForm(fn (Schema $schema): array => $this->getRecordDataForFill($schema, $this->getRecordForFill()));

        $this->action(function (): void {
            $record = $this->getRecordOrFail();

            $schema = $this->getMountedSchema()
                ?? throw new LogicException(sprintf('The action [%s] edits a record through a form, but none resolved — declare schema([...]) or host it where a resolver answers.', $this->getName()));

            $this->process($this->updateRecord(...), ['record' => $record, 'schema' => $schema, 'form' => $schema]);

            $this->success();
        });
    }

    /**
     * The default persistence step `using()` replaces: map the validated —
     * and, where `mutateDataUsing()` asked, mutated — state onto the bound
     * record, then flush it. A path the hook added that no component of the
     * schema maps is refused by name before the record is touched. The record
     * check that ends the mapping reads the schema's own reader, rebound to the
     * manager the flush writes through.
     *
     * @param array<string, mixed> $data
     *
     * @throws LogicException when the hook added a path no component maps
     */
    private function updateRecord(array $data, Schema $schema, object $record): object
    {
        $this->refuseUnmappedData($schema, $data);

        $manager = $this->getEntityManagerForRecord($record);

        $schema->setAssociationReader($schema->getAssociationReader()->withEntityManager($manager));
        $schema->record($record)->getData($data);

        $manager->persist($record);
        $manager->flush();

        return $record;
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Panels\Resources\Pages;

use Sylament\Panels\Resources\Pages\CreateRecord;
use Sylament\Tests\Fixtures\Panels\Resources\PottedPlantResource;
use Sylament\Tests\Fixtures\Schemas\HookLog;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;

/**
 * Records each create hook and its mutator in the request's {@see HookLog}; the mutator upper-cases the name and adds a
 * `status` no field maps when the name is {@see self::UNMAPPED_NAME}, and `beforeCreate()` halts when it is
 * {@see self::HALTING_NAME}.
 */
#[AsLiveComponent]
final class CreateHookedPottedPlant extends CreateRecord
{
    public const string HALTING_NAME = 'Halt before creating';

    public const string UNMAPPED_NAME = 'Add a status';

    protected static string $resource = PottedPlantResource::class;

    private HookLog $hookLog;

    #[Required]
    public function setHookLog(HookLog $hookLog): void
    {
        $this->hookLog = $hookLog;
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->hookLog->record('mutateFormDataBeforeCreate');

        $name = is_string($data['name'] ?? null) ? $data['name'] : '';

        return [
            ...$data,
            'name' => mb_strtoupper($name),
            ...($name === self::UNMAPPED_NAME ? ['status' => 'potted'] : []),
        ];
    }

    protected function beforeCreate(): void
    {
        $this->hookLog->record('beforeCreate');

        if (($this->data['name'] ?? null) === self::HALTING_NAME) {
            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        $this->hookLog->record('afterCreate');
    }
}

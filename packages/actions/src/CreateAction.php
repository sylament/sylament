<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;

class CreateAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'create';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Create');

        $this->successNotificationTitle('Created');

        $this->groupedIcon('heroicons:plus-20-solid');

        $this->action(function (): never {
            throw new LogicException('CreateAction has no URL to open — give it url() or host it on a resource page with a create page; the modal arm is not ported yet.');
        });
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Actions;

use LogicException;
use Sylament\Actions\Concerns\CanCustomizeProcess;

class EditAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'edit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Edit');

        $this->successNotificationTitle('Saved');

        $this->defaultColor('primary');

        $this->icon('heroicons:pencil-square-20-solid');

        $this->action(function (): never {
            throw new LogicException('EditAction has no URL to open — give it url() or host it on a resource page with an edit page; the modal arm is not ported yet.');
        });
    }
}

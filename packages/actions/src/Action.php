<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\CanBeDisabled;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanNotify;
use Sylament\Actions\Concerns\CanOpenModal;
use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Actions\Concerns\CanRequireConfirmation;
use Sylament\Actions\Concerns\HasAction;
use Sylament\Actions\Concerns\HasArguments;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasName;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Support\Component\Concerns\HasColor;
use Sylament\Support\Component\Concerns\HasExtraAttributes;
use Sylament\Support\Component\Concerns\HasIcon;
use Sylament\Support\Component\Concerns\HasIconPosition;
use Sylament\Support\Component\Concerns\HasIconSize;
use Sylament\Support\Component\Concerns\HasTooltip;
use Sylament\Support\Component\ViewComponent;

class Action extends ViewComponent
{
    use BelongsToLiveComponent;
    use CanBeDisabled;
    use CanBeHidden;
    use CanNotify;
    use CanOpenModal;
    use CanOpenUrl;
    use CanRequireConfirmation;
    use HasAction;
    use HasArguments;
    use HasColor;
    use HasExtraAttributes;
    use HasIcon;
    use HasIconPosition;
    use HasIconSize;
    use HasLabel;
    use HasName;
    use HasSize;
    use HasTooltip;
    use InteractsWithRecord;

    protected string $evaluationIdentifier = 'action';

    protected string|Closure|null $template = '@SylamentActions/components/button-action.html.twig';

    final public function __construct(?string $name)
    {
        $this->name($name);
    }

    public static function make(?string $name = null): static
    {
        $action = new static($name);

        $action->configure();

        return $action;
    }
}

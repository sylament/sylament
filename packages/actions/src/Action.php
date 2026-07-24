<?php

declare(strict_types=1);

namespace Sylament\Actions;

use Closure;
use Sylament\Actions\Concerns\BelongsToLiveComponent;
use Sylament\Actions\Concerns\CanBeDisabled;
use Sylament\Actions\Concerns\CanBeHidden;
use Sylament\Actions\Concerns\CanOpenUrl;
use Sylament\Actions\Concerns\HasAction;
use Sylament\Actions\Concerns\HasLabel;
use Sylament\Actions\Concerns\HasName;
use Sylament\Actions\Concerns\HasSize;
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
    use CanOpenUrl;
    use HasAction;
    use HasColor;
    use HasExtraAttributes;
    use HasIcon;
    use HasIconPosition;
    use HasIconSize;
    use HasLabel;
    use HasName;
    use HasSize;
    use HasTooltip;

    protected string $evaluationIdentifier = 'action';

    protected string|Closure|null $template = '@SylamentActions/components/button-action.html.twig';

    protected bool|Closure $isConfirmationRequired = false;

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

    public function requiresConfirmation(bool|Closure $condition = true): static
    {
        $this->isConfirmationRequired = $condition;

        return $this;
    }

    public function isConfirmationRequired(): bool
    {
        return $this->evaluate($this->isConfirmationRequired);
    }
}

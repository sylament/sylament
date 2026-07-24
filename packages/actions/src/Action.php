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
use Sylament\Actions\Concerns\HasLifecycleHooks;
use Sylament\Actions\Concerns\HasName;
use Sylament\Actions\Concerns\HasSize;
use Sylament\Actions\Concerns\InteractsWithRecord;
use Sylament\Actions\Enum\ActionStatus;
use Sylament\Support\Component\Concerns\HasColor;
use Sylament\Support\Component\Concerns\HasExtraAttributes;
use Sylament\Support\Component\Concerns\HasIcon;
use Sylament\Support\Component\Concerns\HasIconPosition;
use Sylament\Support\Component\Concerns\HasIconSize;
use Sylament\Support\Component\Concerns\HasTooltip;
use Sylament\Support\Component\ViewComponent;
use Sylament\Support\Data\ClosureParameter;
use Sylament\Support\Exception\Cancel;
use Sylament\Support\Exception\Halt;

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
    use HasLifecycleHooks;
    use HasName;
    use HasSize;
    use HasTooltip;
    use InteractsWithRecord;

    protected string $evaluationIdentifier = 'action';

    protected string|Closure|null $template = '@SylamentActions/components/button-action.html.twig';

    protected ?ActionStatus $status = null;

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

    /**
     * @param array<string, mixed> $parameters
     */
    public function call(array $parameters = []): mixed
    {
        return $this->evaluate($this->getAction(), $parameters);
    }

    public function cancel(): void
    {
        throw new Cancel();
    }

    public function halt(): void
    {
        throw new Halt();
    }

    public function success(): void
    {
        $this->status = ActionStatus::Success;
    }

    public function failure(): void
    {
        $this->status = ActionStatus::Failure;
    }

    public function getStatus(): ActionStatus
    {
        return $this->status ?? ActionStatus::Success;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'record' => ClosureParameter::make($this->getRecord()),
            'arguments' => ClosureParameter::make($this->getArguments()),
            'liveComponent' => ClosureParameter::make($this->getLiveComponent()),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }
}

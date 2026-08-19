<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Wizard\Step;
use Sylament\Support\Components\Attributes\ExposedLiveComponentMethod;
use Sylament\Support\Components\Concerns\CanBeContained;
use Sylament\Support\Enums\IconPosition;
use Sylament\Support\Exceptions\Halt;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\blank;
use function Sylament\Support\filled;

class Wizard extends Component
{
    use CanBeContained;
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Wizard/index.html.twig';

    protected string|Markup|Action|null $cancelAction = null;

    protected string|Markup|Action|null $submitAction = null;

    protected bool|Closure $isSkippable = false;

    protected string|Closure|null $stepQueryStringKey = null;

    protected Action|Closure|null $modifyNextActionUsing = null;

    protected Action|Closure|null $modifyPreviousActionUsing = null;

    protected int|Closure $startStep = 1;

    protected ?int $currentStepIndex = null;

    protected bool|Closure $isHeaderHidden = false;

    private ?RequestStack $requestStack = null;

    /**
     * @param array<Step>|Closure $steps
     */
    public static function make(array|Closure $steps = []): static
    {
        $wizard = static::newInstance();

        $wizard->steps($steps);

        $wizard->configure();

        return $wizard;
    }

    #[Required]
    public function setRequestStack(?RequestStack $requestStack = null): void
    {
        $this->requestStack = $requestStack;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (Wizard $component): string {
            $statePath = $component->getStatePath();
            $suffix = filled($statePath) ? "{$statePath}::wizard" : 'wizard';

            $label = $component->getLabel();

            if (blank($label)) {
                return $suffix;
            }

            return new AsciiSlugger()->slug((string) $label)->lower()->toString() . '::' . $suffix;
        }, isInheritable: false);

        $this->registerActions([
            fn (Wizard $component): Action => $component->getNextAction(),
            fn (Wizard $component): Action => $component->getPreviousAction(),
        ]);
    }

    #[ExposedLiveComponentMethod]
    public function nextStep(int $currentStepIndex): void
    {
        if (!$this->isSkippable()) {
            $steps = $this->getSteps();

            $currentStep = $steps[$currentStepIndex] ?? null;

            if (!$currentStep instanceof Step) {
                return;
            }

            $nextStep = $steps[$currentStepIndex + 1] ?? null;
            $this->currentStepIndex($currentStepIndex + 1);

            try {
                $currentStep->callBeforeValidation();
                $currentStep->getChildSchema()?->validate();
                $currentStep->callAfterValidation();
                $nextStep?->getChildSchema()?->fillStateWithNull();
            } catch (Halt) {
                return;
            }
        }

        $this->getLiveComponent()->dispatchBrowserEvent('next-wizard-step', ['key' => $this->getKey()]);
    }

    #[ExposedLiveComponentMethod]
    public function previousStep(int $currentStepIndex): void
    {
        $this->currentStepIndex(max(1, $currentStepIndex) - 1);
    }

    public function goToStep(string $step): void
    {
        foreach ($this->getSteps() as $index => $wizardStep) {
            if ($wizardStep->getKey() !== $step) {
                continue;
            }

            if ((!$this->isSkippable()) && ($index > $this->getCurrentStepIndex())) {
                return;
            }

            $this->currentStepIndex($index);

            $this->getLiveComponent()->dispatchBrowserEvent('go-to-wizard-step', [
                'key' => $this->getKey(),
                'step' => $step,
            ]);

            return;
        }
    }

    public function getNextAction(): Action
    {
        $action = Action::make($this->getNextActionName())
            ->label('Next')
            ->iconPosition(IconPosition::After)
            ->liveComponentClickHandlerEnabled(false)
            ->button();

        return $this->evaluate($this->modifyNextActionUsing, [
            'action' => $action,
        ]) ?? $action;
    }

    public function nextAction(?Closure $callback): static
    {
        $this->modifyNextActionUsing = $callback;

        return $this;
    }

    public function getNextActionName(): string
    {
        return 'next';
    }

    public function getPreviousAction(): Action
    {
        $action = Action::make($this->getPreviousActionName())
            ->label('Back')
            ->color('gray')
            ->liveComponentClickHandlerEnabled(false)
            ->button();

        return $this->evaluate($this->modifyPreviousActionUsing, [
            'action' => $action,
        ]) ?? $action;
    }

    public function previousAction(?Closure $callback): static
    {
        $this->modifyPreviousActionUsing = $callback;

        return $this;
    }

    public function getPreviousActionName(): string
    {
        return 'previous';
    }

    /**
     * @param array<Step>|Closure $steps
     */
    public function steps(array|Closure $steps): static
    {
        $this->components($steps);

        return $this;
    }

    public function startOnStep(int|Closure $startStep): static
    {
        $this->startStep = $startStep;

        return $this;
    }

    public function cancelAction(string|Markup|Action|null $action): static
    {
        $this->cancelAction = $action;

        return $this;
    }

    public function submitAction(string|Markup|Action|null $action): static
    {
        $this->submitAction = $action;

        return $this;
    }

    public function skippable(bool|Closure $condition = true): static
    {
        $this->isSkippable = $condition;

        return $this;
    }

    public function persistStepInQueryString(string|Closure|null $key = 'step'): static
    {
        $this->stepQueryStringKey = $key;

        return $this;
    }

    public function hiddenHeader(bool|Closure $condition = true): static
    {
        $this->isHeaderHidden = $condition;

        return $this;
    }

    public function getCancelAction(): string|Markup|Action|null
    {
        return $this->cancelAction;
    }

    public function getSubmitAction(): string|Markup|Action|null
    {
        return $this->submitAction;
    }

    /**
     * The visible steps, in render order — the list every index in this component's
     * API (`nextStep()`, `getStartStep()`, the header) is an index into.
     *
     * @return list<Step>
     */
    public function getSteps(): array
    {
        return array_values(array_filter(
            $this->getChildSchema()?->getComponents(withActions: false, withHidden: false) ?? [],
            static fn (Component|Action|ActionGroup $component): bool => $component instanceof Step,
        ));
    }

    public function getStartStep(): int
    {
        if ($this->isStepPersistedInQueryString()) {
            $queryStringStep = $this->requestStack?->getMainRequest()?->query->get((string) $this->getStepQueryStringKey());

            foreach ($this->getSteps() as $index => $step) {
                if ($step->getKey() !== $queryStringStep) {
                    continue;
                }

                return $index + 1;
            }
        }

        return $this->evaluate($this->startStep);
    }

    /**
     * The absolute key of the step the server renders active — the state the Stimulus
     * controller adopts on connect. Sylament-only: Filament resolves it client-side.
     */
    public function getStartStepKey(): ?string
    {
        return ($this->getSteps()[$this->getStartStep() - 1] ?? null)?->getKey();
    }

    public function getStepQueryStringKey(): ?string
    {
        return $this->evaluate($this->stepQueryStringKey);
    }

    public function isSkippable(): bool
    {
        return (bool) $this->evaluate($this->isSkippable);
    }

    public function isStepPersistedInQueryString(): bool
    {
        return filled($this->getStepQueryStringKey());
    }

    public function isHeaderHidden(): bool
    {
        return (bool) $this->evaluate($this->isHeaderHidden);
    }

    public function getCurrentStepIndex(): int
    {
        return $this->currentStepIndex ??= ($this->getStartStep() - 1);
    }

    protected function currentStepIndex(int $index): static
    {
        $this->currentStepIndex = $index;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $cancelAction = $this->getCancelAction();
        $submitAction = $this->getSubmitAction();

        return [
            'steps' => $this->getSteps(),
            'startStep' => $this->getStartStep(),
            'wizardKey' => $this->getKey(),
            'schemaKey' => $this->getRootContainer()->getKey(),
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'isContained' => $this->isContained(),
            'isHeaderHidden' => $this->isHeaderHidden(),
            'isSkippable' => $this->isSkippable(),
            'stepQueryStringKey' => $this->getStepQueryStringKey(),
            'previousAction' => $this->getAction($this->getPreviousActionName()),
            'nextAction' => $this->getAction($this->getNextActionName()),
            'cancelActionComponent' => $cancelAction instanceof Action ? $cancelAction : null,
            'cancelActionHtml' => $this->renderSlotHtml($cancelAction),
            'submitActionComponent' => $submitAction instanceof Action ? $submitAction : null,
            'submitActionHtml' => $this->renderSlotHtml($submitAction),
        ];
    }

    /**
     * The half of a footer slot the template prints raw: a `Markup` as-is, a plain string
     * escaped, and nothing at all for an `Action` (which renders as a component instead).
     */
    private function renderSlotHtml(string|Markup|Action|null $action): ?Markup
    {
        return match (true) {
            $action instanceof Action, $action === null => null,
            $action instanceof Markup => $action,
            default => new Markup(htmlspecialchars($action, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), 'UTF-8'),
        };
    }
}

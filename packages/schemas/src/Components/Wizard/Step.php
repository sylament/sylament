<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Wizard;

use BackedEnum;
use Closure;
use LogicException;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasLabel;
use Sylament\Schemas\Components\Contracts\CanConcealComponents;
use Sylament\Schemas\Components\Wizard;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Twig\Markup;

use function Sylament\Support\filled;

class Step extends Component implements CanConcealComponents
{
    use HasLabel;

    protected string|Closure|null $template = '@SylamentSchemas/partials/Step/index.html.twig';

    protected ?Closure $afterValidation = null;

    protected ?Closure $beforeValidation = null;

    protected string|Closure|null $description = null;

    protected string|BackedEnum|Markup|Closure|null $icon = null;

    protected string|BackedEnum|Markup|Closure|null $completedIcon = null;

    public static function make(string $label): static
    {
        $step = static::newInstance();

        $step->label($label);

        $step->configure();

        return $step;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->key(function (Step $component): string {
            $statePath = $component->getStatePath();

            return new AsciiSlugger()->slug((string) $component->getLabel())->lower()->toString()
                . '::' . (filled($statePath) ? "{$statePath}::wizard-step" : 'wizard-step');
        }, isInheritable: false);
    }

    public function afterValidation(?Closure $callback): static
    {
        $this->afterValidation = $callback;

        return $this;
    }

    public function beforeValidation(?Closure $callback): static
    {
        $this->beforeValidation = $callback;

        return $this;
    }

    public function description(string|Closure|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function icon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function completedIcon(string|BackedEnum|Markup|Closure|null $icon): static
    {
        $this->completedIcon = $icon;

        return $this;
    }

    public function callAfterValidation(): void
    {
        $this->evaluate($this->afterValidation);
    }

    public function callBeforeValidation(): void
    {
        $this->evaluate($this->beforeValidation);
    }

    public function getDescription(): ?string
    {
        return $this->evaluate($this->description);
    }

    public function getIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->icon);
    }

    public function getCompletedIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->completedIcon);
    }

    /**
     * @return array<string, int|null>
     */
    public function getAllColumns(): array
    {
        if ($this->columns === null) {
            return $this->getContainer()->getAllColumns();
        }

        return parent::getAllColumns();
    }

    public function canConcealComponents(): bool
    {
        return true;
    }

    public function getWizard(): Wizard
    {
        $wizard = $this->getContainer()->getParentComponent();

        if (!$wizard instanceof Wizard) {
            throw new LogicException('A ' . static::class . ' renders only inside a ' . Wizard::class . ' — pass it through Wizard::make([...]) or steps().');
        }

        return $wizard;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        $wizard = $this->getWizard();
        $id = $this->getId();
        $label = $this->getLabel();

        $hasHeaderReference = filled($id) && !$wizard->isHeaderHidden();

        return [
            'stepKey' => $this->getKey(),
            'stepId' => $id,
            'isActive' => $wizard->getStartStepKey() === $this->getKey(),
            'hasHeaderReference' => $hasHeaderReference,
            'labelText' => $label instanceof Markup ? strip_tags((string) $label) : $label,
            'contentSchema' => $this->getChildSchema(),
        ];
    }
}

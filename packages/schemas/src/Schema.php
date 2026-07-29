<?php

declare(strict_types=1);

namespace Sylament\Schemas;

use Closure;
use Sylament\Actions\Action;
use Sylament\Actions\ActionGroup;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Components\Concerns\HasStateBindingModifiers;
use Sylament\Schemas\Components\Concerns\CanAccessProperties;
use Sylament\Schemas\Concerns\CanBeDisabled;
use Sylament\Schemas\Components\Concerns\HasColumns;
use Sylament\Schemas\Components\Concerns\HasGap;
use Sylament\Schemas\Concerns\BelongsToLiveComponent;
use Sylament\Schemas\Concerns\BelongsToParentComponent;
use Sylament\Schemas\Concerns\CanBeEmbeddedInParentComponent;
use Sylament\Schemas\Concerns\CanBeInline;
use Sylament\Schemas\Concerns\CanModifyActions;
use Sylament\Schemas\Concerns\HasComponents;
use Sylament\Schemas\Concerns\HasHeadings;
use Sylament\Schemas\Concerns\HasKey;
use Sylament\Schemas\Concerns\HasState;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Components\Concerns\HasAlignment;
use Sylament\Support\Components\Concerns\HasExtraAttributes;
use Sylament\Support\Components\ViewComponent;
use Sylament\Support\Evaluation\ClosureParameter;
use Twig\Environment;
use Twig\Markup;

use function Sylament\Support\filled;

class Schema extends ViewComponent
{
    use BelongsToLiveComponent;
    use BelongsToParentComponent;
    use CanAccessProperties;
    use CanBeDisabled;
    use CanBeEmbeddedInParentComponent;
    use CanBeInline;
    use CanModifyActions;
    use HasAlignment;
    use HasColumns {
        getAllColumns as private getOwnAllColumns;
    }
    use HasComponents;
    use HasExtraAttributes;
    use HasGap;
    use HasHeadings;
    use HasKey;
    use HasState;
    use HasStateBindingModifiers;

    protected string $evaluationIdentifier = 'schema';

    protected string|Closure|null $template = '@SylamentSchemas/partials/Schema/index.html.twig';

    public static function make(?InteractsWithSchemas $liveComponent = null): static
    {
        $schema = static::newInstance();

        $schema->liveComponent($liveComponent);

        $schema->configure();

        return $schema;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): ?ClosureParameter
    {
        return match ($parameterName) {
            'container' => ClosureParameter::make($this),
            'liveComponent' => ClosureParameter::make($this->liveComponent),
            default => parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName),
        };
    }

    public function isLive(): bool
    {
        return $this->evaluate($this->isLive) ?? $this->getParentComponent()?->isLive() ?? false;
    }

    public function isLiveOnBlur(): bool
    {
        if ($this->evaluate($this->isLive) !== null) {
            return $this->isLiveOnBlur;
        }

        return $this->getParentComponent()?->isLiveOnBlur() ?? false;
    }

    public function isLiveDebounced(): bool
    {
        if ($this->isLiveOnBlur) {
            return false;
        }

        if (filled($this->liveDebounce)) {
            return true;
        }

        return $this->getParentComponent()?->isLiveDebounced() ?? false;
    }

    public function getLiveDebounce(): int|string|null
    {
        if ($this->isLiveOnBlur) {
            return null;
        }

        if (filled($this->liveDebounce)) {
            return $this->liveDebounce;
        }

        return $this->getParentComponent()?->getLiveDebounce();
    }

    public function hasGap(): bool
    {
        return $this->evaluate($this->hasGap)
            ?? $this->getParentComponent()?->hasGap()
            ?? true;
    }

    public function isDense(): bool
    {
        $isDense = $this->evaluate($this->isDense);
        if ($isDense !== null) {
            return $isDense;
        }

        if ($this->isInline()) {
            return true;
        }

        return $this->getParentComponent()?->isDense() ?? false;
    }

    /**
     * @return array<string, ?int>
     */
    public function getAllColumns(): array
    {
        return $this->getParentComponent()?->getAllColumns() ?? $this->getOwnAllColumns();
    }

    public function render(Environment $twig): ?string
    {
        if ($this->getComponents(withHidden: false) === []) {
            return null;
        }

        return parent::render($twig);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraTemplateData(): array
    {
        return [
            'visibleChildren' => array_map(
                static fn (Component|Action|ActionGroup $childComponent): array => [
                    'component' => $childComponent,
                    'isAction' => ($childComponent instanceof Action) || ($childComponent instanceof ActionGroup),
                ],
                $this->getComponents(withHidden: false),
            ),
        ];
    }

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure $components
     */
    public static function start(array|Component|Action|ActionGroup|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignStart();
    }

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure $components
     */
    public static function end(array|Component|Action|ActionGroup|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignEnd();
    }

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure $components
     */
    public static function center(array|Component|Action|ActionGroup|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignCenter();
    }

    /**
     * @param array<Component|Action|ActionGroup|string|Markup>|Component|Action|ActionGroup|string|Markup|Closure $components
     */
    public static function between(array|Component|Action|ActionGroup|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignBetween();
    }
}

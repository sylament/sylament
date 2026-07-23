<?php

declare(strict_types=1);

namespace Sylament\Schemas;

use Closure;
use Sylament\Schemas\Components\Component;
use Sylament\Schemas\Concerns\HasColumns;
use Sylament\Schemas\Concerns\HasGap;
use Sylament\Schemas\Concerns\BelongsToLiveComponent;
use Sylament\Schemas\Concerns\BelongsToParentComponent;
use Sylament\Schemas\Concerns\CanBeEmbeddedInParentComponent;
use Sylament\Schemas\Concerns\CanBeInline;
use Sylament\Schemas\Concerns\HasComponents;
use Sylament\Schemas\Concerns\HasHeadings;
use Sylament\Schemas\Concerns\HasKey;
use Sylament\Schemas\Concerns\HasState;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Component\Concerns\HasAlignment;
use Sylament\Support\Component\Concerns\HasExtraAttributes;
use Sylament\Support\Component\ViewComponent;
use Sylament\Support\Data\ClosureParameter;
use Twig\Markup;

class Schema extends ViewComponent
{
    use BelongsToLiveComponent;
    use BelongsToParentComponent;
    use CanBeEmbeddedInParentComponent;
    use CanBeInline;
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

    protected string $evaluationIdentifier = 'schema';

    protected string|Closure|null $template = '@SylamentSchemas/components/Schema.html.twig';

    final public function __construct(?InteractsWithSchemas $liveComponent = null)
    {
        $this->liveComponent($liveComponent);
    }

    public static function make(?InteractsWithSchemas $liveComponent = null): static
    {
        $schema = new static($liveComponent);

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

    /**
     * @param array<Component|string|Markup>|Component|string|Markup|Closure $components
     */
    public static function start(array|Component|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignStart();
    }

    /**
     * @param array<Component|string|Markup>|Component|string|Markup|Closure $components
     */
    public static function end(array|Component|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignEnd();
    }

    /**
     * @param array<Component|string|Markup>|Component|string|Markup|Closure $components
     */
    public static function center(array|Component|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignCenter();
    }

    /**
     * @param array<Component|string|Markup>|Component|string|Markup|Closure $components
     */
    public static function between(array|Component|string|Markup|Closure $components): static
    {
        return static::make()
            ->components($components)
            ->alignBetween();
    }
}

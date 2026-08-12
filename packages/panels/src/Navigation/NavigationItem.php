<?php

declare(strict_types=1);

namespace Sylament\Panels\Navigation;

use BackedEnum;
use Closure;
use Sylament\Support\Components\Component;
use Sylament\Support\Components\Concerns\HasIcon;
use Sylament\Support\Evaluation\ClosureParameter;
use Sylament\Support\Icons\IconManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Twig\Markup;

use function Sylament\Support\filled;

class NavigationItem extends Component
{
    use HasIcon;

    protected string|BackedEnum|Markup|Closure|null $activeIcon = null;

    protected string|Closure $label;

    protected string|Closure|null $badge = null;

    /**
     * @var string | array<string> | Closure | null
     */
    protected string|array|Closure|null $badgeColor = null;

    protected string|Closure|null $group = null;

    protected bool|Closure|null $isActive = null;

    protected bool|Closure $isHidden = false;

    protected bool|Closure $isVisible = true;

    protected bool|Closure $shouldOpenUrlInNewTab = false;

    protected int|Closure|null $sort = null;

    protected string|Closure|null $url = null;

    private ?UrlGeneratorInterface $urlGenerator = null;

    private ?RequestStack $requestStack = null;

    private ?IconManager $iconManager = null;

    public static function make(string|Closure|null $label = null): static
    {
        $item = static::newInstance();

        if (filled($label)) {
            $item->label($label);
        }

        $item->configure();

        return $item;
    }

    #[Required]
    public function setEvaluationServices(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack, IconManager $iconManager): void
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
        $this->iconManager = $iconManager;
    }

    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function activeIcon(string|BackedEnum|Markup|Closure|null $activeIcon): static
    {
        $this->activeIcon = $activeIcon;

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function badge(string|Closure|null $badge, string|array|Closure|null $color = null): static
    {
        $this->badge = $badge;

        if ($color !== null) {
            $this->badgeColor($color);
        }

        return $this;
    }

    /**
     * @param  string | array<string> | Closure | null  $color
     */
    public function badgeColor(string|array|Closure|null $color): static
    {
        $this->badgeColor = $color;

        return $this;
    }

    public function group(string|Closure|null $group): static
    {
        $this->group = $group;

        return $this;
    }

    public function isActiveWhen(?Closure $callback): static
    {
        $this->isActive = $callback;

        return $this;
    }

    public function hidden(bool|Closure $condition = true): static
    {
        $this->isHidden = $condition;

        return $this;
    }

    public function visible(bool|Closure $condition = true): static
    {
        $this->isVisible = $condition;

        return $this;
    }

    public function sort(int|Closure|null $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function url(string|Closure|null $url, bool|Closure|null $shouldOpenInNewTab = null): static
    {
        $this->url = $url;

        if ($shouldOpenInNewTab !== null) {
            $this->openUrlInNewTab($shouldOpenInNewTab);
        }

        return $this;
    }

    public function openUrlInNewTab(bool|Closure $condition = true): static
    {
        $this->shouldOpenUrlInNewTab = $condition;

        return $this;
    }

    public function getActiveIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->activeIcon);
    }

    public function getLabel(): string
    {
        return $this->evaluate($this->label);
    }

    public function getBadge(): ?string
    {
        return $this->evaluate($this->badge);
    }

    /**
     * @return string | array<string> | null
     */
    public function getBadgeColor(): string|array|null
    {
        return $this->evaluate($this->badgeColor);
    }

    public function getGroup(): ?string
    {
        return $this->evaluate($this->group);
    }

    public function getSort(): int
    {
        return $this->evaluate($this->sort) ?? -1;
    }

    public function getUrl(): ?string
    {
        return $this->evaluate($this->url);
    }

    public function shouldOpenUrlInNewTab(): bool
    {
        return (bool) $this->evaluate($this->shouldOpenUrlInNewTab);
    }

    public function isActive(): bool
    {
        if ($this->isActive instanceof Closure) {
            $this->isActive = (bool) $this->evaluate($this->isActive);
        }

        return (bool) $this->isActive;
    }

    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return ! $this->evaluate($this->isVisible);
    }

    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): ?ClosureParameter
    {
        return match ($parameterType) {
            UrlGeneratorInterface::class => $this->urlGenerator instanceof UrlGeneratorInterface ? ClosureParameter::make($this->urlGenerator) : null,
            RequestStack::class => $this->requestStack instanceof RequestStack ? ClosureParameter::make($this->requestStack) : null,
            IconManager::class => $this->iconManager instanceof IconManager ? ClosureParameter::make($this->iconManager) : null,
            default => null,
        };
    }
}

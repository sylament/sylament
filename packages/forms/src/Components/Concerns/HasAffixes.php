<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use BackedEnum;
use Closure;
use Sylament\Actions\Action;
use Sylament\Support\Enums\Size;
use Twig\Markup;

use function Sylament\Support\wrap;

trait HasAffixes
{
    /**
     * @var array<string, Action>|null
     */
    protected ?array $cachedSuffixActions = null;

    /**
     * @var array<Action|Closure>
     */
    protected array $suffixActions = [];

    protected string|Markup|Closure|null $suffixLabel = null;

    /**
     * @var array<string, Action>|null
     */
    protected ?array $cachedPrefixActions = null;

    /**
     * @var array<Action|Closure>
     */
    protected array $prefixActions = [];

    protected string|Markup|Closure|null $prefixLabel = null;

    protected string|BackedEnum|Markup|Closure|null $prefixIcon = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $prefixIconColor = null;

    protected string|BackedEnum|Markup|Closure|null $suffixIcon = null;

    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $suffixIconColor = null;

    protected bool|Closure $isPrefixInline = false;

    protected bool|Closure $isSuffixInline = false;

    public function prefix(string|Markup|Closure|null $label, bool|Closure $isInline = false): static
    {
        $this->prefixLabel = $label;
        $this->inlinePrefix($isInline);

        return $this;
    }

    public function postfix(string|Markup|Closure|null $label, bool|Closure $isInline = false): static
    {
        return $this->suffix($label, $isInline);
    }

    public function suffix(string|Markup|Closure|null $label, bool|Closure $isInline = false): static
    {
        $this->suffixLabel = $label;
        $this->inlineSuffix($isInline);

        return $this;
    }

    public function prefixAction(Action|Closure $action, bool|Closure $isInline = false): static
    {
        $this->prefixActions([$action], $isInline);

        return $this;
    }

    /**
     * @param array<Action|Closure> $actions
     */
    public function prefixActions(array $actions, bool|Closure $isInline = false): static
    {
        $this->prefixActions = [
            ...$this->prefixActions,
            ...$actions,
        ];
        $this->inlinePrefix($isInline);

        return $this;
    }

    public function suffixAction(Action|Closure $action, bool|Closure $isInline = false): static
    {
        $this->suffixActions([$action], $isInline);

        return $this;
    }

    /**
     * @param array<Action|Closure> $actions
     */
    public function suffixActions(array $actions, bool|Closure $isInline = false): static
    {
        $this->suffixActions = [
            ...$this->suffixActions,
            ...$actions,
        ];
        $this->inlineSuffix($isInline);

        return $this;
    }

    public function inlinePrefix(bool|Closure $isInline = true): static
    {
        $this->isPrefixInline = $isInline;

        return $this;
    }

    public function inlineSuffix(bool|Closure $isInline = true): static
    {
        $this->isSuffixInline = $isInline;

        return $this;
    }

    public function prefixIcon(string|BackedEnum|Markup|Closure|null $icon, bool|Closure $isInline = false): static
    {
        $this->prefixIcon = $icon;
        $this->inlinePrefix($isInline);

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function prefixIconColor(string|array|Closure|null $color = null): static
    {
        $this->prefixIconColor = $color;

        return $this;
    }

    public function suffixIcon(string|BackedEnum|Markup|Closure|null $icon, bool|Closure $isInline = false): static
    {
        $this->suffixIcon = $icon;
        $this->inlineSuffix($isInline);

        return $this;
    }

    /**
     * @param string|array<string>|Closure|null $color
     */
    public function suffixIconColor(string|array|Closure|null $color = null): static
    {
        $this->suffixIconColor = $color;

        return $this;
    }

    /**
     * @return array<string, Action>
     */
    public function getPrefixActions(): array
    {
        return $this->cachedPrefixActions ?? $this->cachePrefixActions();
    }

    /**
     * @return array<string, Action>
     */
    public function cachePrefixActions(): array
    {
        $this->cachedPrefixActions = [];

        foreach ($this->prefixActions as $prefixAction) {
            foreach (wrap($this->evaluate($prefixAction)) as $action) {
                $this->cachedPrefixActions[$action->getName()] = $this->prepareAction(
                    $action
                        ->defaultSize(Size::Small)
                        ->defaultTemplate(Action::ICON_BUTTON_TEMPLATE),
                );
            }
        }

        return $this->cachedPrefixActions;
    }

    /**
     * @return array<string, Action>
     */
    public function getSuffixActions(): array
    {
        return $this->cachedSuffixActions ?? $this->cacheSuffixActions();
    }

    /**
     * @return array<string, Action>
     */
    public function cacheSuffixActions(): array
    {
        $this->cachedSuffixActions = [];

        foreach ($this->suffixActions as $suffixAction) {
            foreach (wrap($this->evaluate($suffixAction)) as $action) {
                $this->cachedSuffixActions[$action->getName()] = $this->prepareAction(
                    $action
                        ->defaultSize(Size::Small)
                        ->defaultTemplate(Action::ICON_BUTTON_TEMPLATE),
                );
            }
        }

        return $this->cachedSuffixActions;
    }

    public function getPrefixLabel(): string|Markup|null
    {
        return $this->evaluate($this->prefixLabel);
    }

    public function getSuffixLabel(): string|Markup|null
    {
        return $this->evaluate($this->suffixLabel);
    }

    public function getPrefixIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->prefixIcon);
    }

    public function getSuffixIcon(): string|BackedEnum|Markup|null
    {
        return $this->evaluate($this->suffixIcon);
    }

    /**
     * @return string|array<string>|null
     */
    public function getPrefixIconColor(): string|array|null
    {
        return $this->evaluate($this->prefixIconColor);
    }

    /**
     * @return string|array<string>|null
     */
    public function getSuffixIconColor(): string|array|null
    {
        return $this->evaluate($this->suffixIconColor);
    }

    public function isPrefixInline(): bool
    {
        return (bool) $this->evaluate($this->isPrefixInline);
    }

    public function isSuffixInline(): bool
    {
        return (bool) $this->evaluate($this->isSuffixInline);
    }
}

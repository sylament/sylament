<?php

declare(strict_types=1);

namespace Sylament\Forms\Components;

use Closure;
use Stringable;
use Sylament\Forms\Components\Concerns\HasAffixes;
use Sylament\Forms\Components\Concerns\HasExtraInputAttributes;
use Sylament\Forms\Components\Concerns\HasNestedRecursiveValidationConstraints;
use Sylament\Schemas\Components\Concerns\CanStripCharactersFromState;
use Sylament\Schemas\Components\Concerns\CanTrimState;
use Sylament\Schemas\State\Casts\Contracts\StateCast;
use Sylament\Schemas\State\Casts\StripCharactersStateCast;
use Sylament\Support\Components\Concerns\HasColor;
use Sylament\Support\Components\Concerns\HasReorderAnimationDuration;

use function Sylament\Support\blank;

class TagsInput extends Field implements Contracts\HasAffixes, Contracts\HasNestedRecursiveValidationConstraints
{
    use CanStripCharactersFromState;
    use CanTrimState;
    use HasAffixes;
    use HasColor;
    use HasExtraInputAttributes;
    use HasNestedRecursiveValidationConstraints;
    use HasReorderAnimationDuration;

    protected string|Closure|null $template = '@SylamentForms/partials/TagsInput/index.html.twig';

    protected bool|Closure $isReorderable = false;

    protected string|Closure|null $separator = null;

    /**
     * @var array<string>|Closure
     */
    protected array|Closure $splitKeys = [];

    /**
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $suggestions = null;

    protected string|Closure|null $tagPrefix = null;

    protected string|Closure|null $tagSuffix = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);

        $this->afterStateHydrated(static function (TagsInput $component): void {
            $component->hydrateTags();
        });

        $this->dehydrateStateUsing(static function (TagsInput $component, mixed $state): mixed {
            $separator = $component->getSeparator();

            if (! $separator || ! is_array($state)) {
                return $state;
            }

            return implode($separator, array_map(
                static fn (mixed $tag): string => is_scalar($tag) || $tag instanceof Stringable ? (string) $tag : '',
                $state,
            ));
        });

        $this->placeholder('New tag');

        $this->reorderAnimationDuration(100);
    }

    public function hydrateTags(): void
    {
        $state = $this->getState();

        if (is_array($state)) {
            return;
        }

        if (! ($separator = $this->getSeparator())) {
            $this->state([]);

            return;
        }

        $state = is_string($state) ? $state : null;

        $tags = explode($separator, $state ?? '');

        if (count($tags) === 1 && blank($state)) {
            $tags = [];
        }

        $this->state($tags);
    }

    public function tagPrefix(string|Closure|null $prefix): static
    {
        $this->tagPrefix = $prefix;

        return $this;
    }

    public function tagSuffix(string|Closure|null $suffix): static
    {
        $this->tagSuffix = $suffix;

        return $this;
    }

    public function reorderable(bool|Closure $condition = true): static
    {
        $this->isReorderable = $condition;

        return $this;
    }

    public function separator(string|Closure|null $separator = ','): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * @param array<string>|Closure $keys
     */
    public function splitKeys(array|Closure $keys): static
    {
        $this->splitKeys = $keys;

        return $this;
    }

    /**
     * @param array<string>|Closure $suggestions
     */
    public function suggestions(array|Closure $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    public function getTagPrefix(): ?string
    {
        return $this->evaluate($this->tagPrefix);
    }

    public function getTagSuffix(): ?string
    {
        return $this->evaluate($this->tagSuffix);
    }

    public function getSeparator(): ?string
    {
        return $this->evaluate($this->separator);
    }

    /**
     * @return array<string>
     */
    public function getSplitKeys(): array
    {
        return $this->evaluate($this->splitKeys);
    }

    /**
     * @return array<string>
     */
    public function getSuggestions(): array
    {
        return $this->evaluate($this->suggestions) ?? [];
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->isReorderable);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...parent::getDefaultStateCasts(),
            ...($this->hasStripCharacters() ? [new StripCharactersStateCast(array_values($this->getStripCharacters()))] : []),
        ];
    }

    public function mutateDehydratedState(mixed $state): mixed
    {
        return parent::mutateDehydratedState(
            is_array($state)
                ? array_map($this->trimState(...), $state)
                : $this->trimState($state),
        );
    }

    public function mutatesDehydratedState(): bool
    {
        return parent::mutatesDehydratedState() || $this->isTrimmed();
    }

    public function mutateStateForValidation(mixed $state): mixed
    {
        $mutate = fn (mixed $value): mixed => $this->trimState($this->stripCharactersFromState($value));

        return parent::mutateStateForValidation(is_array($state) ? array_map($mutate, $state) : $mutate($state));
    }

    public function mutatesStateForValidation(): bool
    {
        return parent::mutatesStateForValidation() || $this->hasStripCharacters() || $this->isTrimmed();
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

use function Sylament\Support\wrap;

trait CanStripCharactersFromState
{
    /**
     * @var string|array<string>|Closure|null
     */
    protected string|array|Closure|null $stripCharacters = null;

    /**
     * @var array<string>
     */
    protected array $cachedStripCharacters;

    /**
     * @param string|array<string>|Closure|null $characters
     */
    public function stripCharacters(string|array|Closure|null $characters): static
    {
        $this->stripCharacters = $characters;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getStripCharacters(): array
    {
        return $this->cachedStripCharacters ??= wrap($this->evaluate($this->stripCharacters));
    }

    public function hasStripCharacters(): bool
    {
        return $this->getStripCharacters() !== [];
    }

    protected function stripCharactersFromState(mixed $state): mixed
    {
        if (! is_string($state)) {
            return $state;
        }

        $stripCharacters = $this->getStripCharacters();

        if ($stripCharacters === []) {
            return $state;
        }

        return str_replace($stripCharacters, '', $state);
    }
}

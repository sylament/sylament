<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components\Concerns;

use Closure;

trait HasId
{
    /**
     * @var string|Closure():string|null
     */
    protected string|Closure|null $id = null;

    /**
     * @param string|callable():string|null $id
     */
    public function id(string|callable|null $id): static
    {
        $this->id = $this->evaluatable($id);

        return $this;
    }

    public function getId(): ?string
    {
        return $this->getCustomId() ?? $this->getKey();
    }

    public function getCustomId(): ?string
    {
        $id = $this->evaluate($this->id);

        if ($id === null) {
            return null;
        }

        // Security: strip characters that could break out of a quoted HTML attribute
        // or a JS string, so every downstream sink embedding this id is safe without
        // per-sink escaping. Legitimate ids never contain these characters.
        return preg_replace('/[<>"\'`\x00-\x1F\x7F]/', '', $id);
    }
}

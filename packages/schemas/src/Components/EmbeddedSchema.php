<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;

class EmbeddedSchema extends Component
{
    protected string|Closure|null $template = '@SylamentSchemas/partials/EmbeddedSchema/index.html.twig';

    protected string|Closure $name;

    public static function make(string $name): static
    {
        $embeddedSchema = static::newInstance();

        $embeddedSchema->name($name);

        $embeddedSchema->configure();

        return $embeddedSchema;
    }

    public function name(string|Closure $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * The host schema this renders in place — a name the host's `schemas()`
     * declares or has cached.
     */
    public function getName(): string
    {
        return $this->evaluate($this->name);
    }
}

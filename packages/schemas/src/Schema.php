<?php

declare(strict_types=1);

namespace Sylament\Schemas;

use Closure;
use Sylament\Schemas\Concerns\BelongsToLiveComponent;
use Sylament\Schemas\Concerns\HasComponents;
use Sylament\Schemas\Contracts\InteractsWithSchemas;
use Sylament\Support\Component\ViewComponent;

class Schema extends ViewComponent
{
    use BelongsToLiveComponent;
    use HasComponents;

    protected string|Closure|null $template = '@SylamentSchemas/components/Schema.html.twig';

    public function __construct(?InteractsWithSchemas $interactsWithSchemas = null)
    {
        $this->liveComponent($interactsWithSchemas);
    }

    public static function make(?InteractsWithSchemas $liveComponent = null): static
    {
        $schema = new static($liveComponent);

        $schema->configure();

        return $schema;
    }
}

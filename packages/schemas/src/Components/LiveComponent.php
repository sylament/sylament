<?php

declare(strict_types=1);

namespace Sylament\Schemas\Components;

use Closure;
use LogicException;

class LiveComponent extends Component
{
    protected string|Closure|null $template = '@SylamentSchemas/partials/LiveComponent/index.html.twig';

    protected string|Closure $component;

    /**
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $data = [];

    /**
     * @param array<string, mixed>|Closure $data
     */
    public static function make(string|Closure $component, array|Closure $data = []): static
    {
        $liveComponent = static::newInstance();

        $liveComponent->component($component);
        $liveComponent->data($data);

        $liveComponent->configure();

        return $liveComponent;
    }

    public function component(string|Closure $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * The registered name of the live component to mount — a
     * `twig_component.defaults`-derived name or, for a class registered under
     * its own FQCN, that class-string.
     */
    public function getComponent(): string
    {
        return $this->evaluate($this->component);
    }

    /**
     * @param array<string, mixed>|Closure $data
     */
    public function data(array|Closure $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->evaluate($this->data);
    }

    /**
     * The props the child is mounted with — exactly what {@see data()} names.
     *
     * @return array<string, mixed>
     */
    public function getComponentProperties(): array
    {
        return $this->getData();
    }

    /**
     * The child's explicit live id, sanitised out of
     * {@see Concerns\HasKey::getLiveComponentKey()} or, hostless, the bare key.
     */
    public function getChildId(): string
    {
        $key = $this->getLiveComponentKey() ?? $this->getKey();

        if ($key === null || $key === '') {
            throw new LogicException('A ' . static::class . ' embed needs a key() — an unkeyed child cannot keep a stable identity across renders.');
        }

        return 'sy-live-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $key);
    }
}

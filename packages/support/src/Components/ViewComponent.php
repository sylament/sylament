<?php

declare(strict_types=1);

namespace Sylament\Support\Components;

use Closure;
use Twig\Environment;

abstract class ViewComponent extends Component
{
    protected string|Closure|null $template = null;

    /**
     * @var array<array<mixed>|Closure>
     */
    protected array $templateData = [];

    /**
     * @param array<mixed>|Closure $templateData
     */
    public function template(
        string|Closure|null $template,
        array|Closure $templateData = [],
    ): static {
        $this->template = $template;
        if ($templateData !== []) {
            $this->templateData($templateData);
        }

        return $this;
    }

    public function getTemplate(): ?string
    {
        return $this->evaluate($this->template);
    }

    /**
     * @param array<mixed>|Closure $templateData
     */
    public function templateData(
        array|Closure $templateData,
    ): static {
        $this->templateData[] = $templateData;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getTemplateData(): array
    {
        $templateData = [];
        foreach ($this->templateData as $data) {
            foreach ($this->evaluate($data) ?: [] as $key => $value) {
                $templateData[$key] = $value;
            }
        }

        return $templateData;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getExtraTemplateData(): array
    {
        return [];
    }

    public function render(Environment $twig): ?string
    {
        $template = $this->getTemplate();
        if ($template === null) {
            return null;
        }

        return $twig->render($template, [
            'component' => $this,
            ...$this->getExtraTemplateData(),
            ...$this->getTemplateData(),
        ]);
    }
}

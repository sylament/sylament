<?php

declare(strict_types=1);

namespace Sylament\Support\Component;

use Closure;
use Twig\Environment;

abstract class ViewComponent extends Component
{
    /**
     * @var string|Closure():string|null
     */
    protected string|Closure|null $template = null;

    /**
     * @var array<array<mixed>|Closure():array<mixed>>
     */
    protected array $templateData = [];

    /**
     * @param string|callable():string|null $template
     * @param array<mixed>|callable():array<mixed> $templateData
     */
    public function template(
        string|callable|null $template,
        array|callable $templateData = [],
    ): static {
        $this->template = $this->evaluatable($template);
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
     * @param array<mixed>|callable():array<mixed> $templateData
     */
    public function templateData(
        array|callable $templateData,
    ): static {
        $this->templateData[] = $this->evaluatable($templateData);

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

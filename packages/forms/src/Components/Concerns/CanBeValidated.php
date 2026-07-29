<?php

declare(strict_types=1);

namespace Sylament\Forms\Components\Concerns;

use Closure;

trait CanBeValidated
{
    /**
     * @var array<string, string|Closure>
     */
    protected array $validationMessages = [];

    /**
     * @param array<string, string|Closure> $messages
     */
    public function validationMessages(array $messages): static
    {
        $this->validationMessages = $messages;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getValidationMessages(): array
    {
        $messages = [];

        foreach ($this->validationMessages as $key => $message) {
            $messages[$key] = $this->evaluate($message);
        }

        return array_filter($messages);
    }
}

<?php

declare(strict_types=1);

namespace Sylament\Schemas\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ValidationException extends UnprocessableEntityHttpException
{
    /**
     * @param array<string, list<string>> $errors messages keyed by absolute state path
     */
    public function __construct(protected array $errors)
    {
        parent::__construct('Schema validation failed.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

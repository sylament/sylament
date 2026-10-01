<?php

declare(strict_types=1);

namespace Sylament\Schemas\Validation;

use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class RecordKeyGuard
{
    /**
     * A record-key rule's refusals as a constraint validated against the record,
     * one violation per refusal at its path, so each message is translated by
     * the validator that runs it and filed as the record's own violations are.
     *
     * @internal the record check behind a schema's getData() and an editable cell's write
     *
     * @param array<string, string> $refusals the message, keyed by the record property path it belongs to
     */
    public static function constraint(array $refusals): Callback
    {
        return new Callback(static function (object $record, ExecutionContextInterface $context) use ($refusals): void {
            foreach ($refusals as $path => $message) {
                $context->buildViolation($message)->atPath($path)->addViolation();
            }
        });
    }
}

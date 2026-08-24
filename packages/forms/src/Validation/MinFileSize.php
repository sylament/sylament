<?php

declare(strict_types=1);

namespace Sylament\Forms\Validation;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * The minimum Symfony's `File` has no counterpart for: it caps a file's size and never floors it.
 * The unit is kibibytes, matching Laravel's `min:` rule for files, which is what
 * `BaseFileUpload::minSize()` translates.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class MinFileSize extends Constraint
{
    /**
     * @param string[]|null $groups
     */
    public function __construct(
        public int $kilobytes,
        public string $message = 'The file is too small ({{ size }} {{ suffix }}). Minimum size is {{ limit }} {{ suffix }}.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}

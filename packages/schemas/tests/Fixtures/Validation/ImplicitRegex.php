<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Validation;

use Sylament\Schemas\Validation\Contracts\ImplicitConstraint;
use Symfony\Component\Validator\Constraints\Regex;

final class ImplicitRegex extends Regex implements ImplicitConstraint
{
}

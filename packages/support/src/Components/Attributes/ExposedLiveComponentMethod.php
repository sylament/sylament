<?php

declare(strict_types=1);

namespace Sylament\Support\Components\Attributes;

use Attribute;

/**
 * Marks a schema-component method the host may dispatch to from the browser through `callSchemaComponentMethod()`.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ExposedLiveComponentMethod
{
}

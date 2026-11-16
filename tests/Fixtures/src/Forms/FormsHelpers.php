<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use LogicException;
use Symfony\Component\DomCrawler\Crawler;

trait FormsHelpers
{
    /**
     * The value the render's one select JS control hands its Stimulus controller under
     * `$name`, as the attribute spells it (`'true'`, `'50'`, a message), a list or a map
     * JSON-decoded, and null when the template passed none.
     */
    protected function selectControllerValue(Crawler $render, string $name): mixed
    {
        $control = $render->filter(Selectors::JS_CONTROL);

        if ($control->count() !== 1) {
            throw new LogicException(sprintf('The render holds %d JS select controls where one was expected.', $control->count()));
        }

        $value = $control->attr(sprintf('data-sylament--forms--select-%s-value', mb_strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $name))));

        if ($value === null || !in_array($value[0] ?? '', ['[', '{'], true)) {
            return $value;
        }

        return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
    }
}

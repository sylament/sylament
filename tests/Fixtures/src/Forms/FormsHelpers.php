<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Forms;

use LogicException;
use Sylament\Actions\Testing\Testable;
use Sylament\Tests\Fixtures\Schemas\HookLog;
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

    /**
     * Each radio option of the render as its value and the description line shown under
     * it, null where the option shows none.
     *
     * @return list<array{?string, ?string}>
     */
    protected function radioDescriptions(Crawler $render): array
    {
        return $render->filter(Selectors::RADIO_GROUP . ' label')->each(static fn (Crawler $option): array => [
            $option->filter(Selectors::RADIO)->attr('value'),
            $option->filter(Selectors::RADIO_OPTION_DESCRIPTION)->count() === 1 ? $option->filter(Selectors::RADIO_OPTION_DESCRIPTION)->text() : null,
        ]);
    }

    /**
     * What the file field method the tester's last request called answered, as the browser
     * event that carries it back hands it to the controller.
     */
    protected function fileUploadMethodResult(Testable $tester): mixed
    {
        $events = $this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result');

        if (!is_array($events[0] ?? null) || !array_key_exists('result', $events[0])) {
            throw new LogicException('The request dispatched no schema component method result.');
        }

        return $events[0]['result'];
    }

    /**
     * The state the last save of the request recorded, read from the request's {@see HookLog}.
     *
     * @return array<string, mixed>
     */
    protected function savedUploadState(): array
    {
        $hookLog = static::getContainer()->get(HookLog::class);

        if (!$hookLog instanceof HookLog) {
            throw new LogicException("The kernel's hook log is not a HookLog.");
        }

        return FileUploadFixtures::savedState($hookLog);
    }
}

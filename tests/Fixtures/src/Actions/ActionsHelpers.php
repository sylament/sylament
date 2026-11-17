<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Actions;

use Sylament\Actions\Testing\Testable;

trait ActionsHelpers
{
    /**
     * The options the mounted action's record select answers a typed search with, by value,
     * as its Stimulus controller asks for them.
     *
     * @return array<string, string>
     */
    protected function recordSelectSearch(Testable $tester, string $search, string $component = 'mountedActionSchema0.recordId'): array
    {
        $tester->call('callSchemaComponentMethod', ['componentKey' => $component, 'method' => 'getSearchResultsForJs', 'arguments' => ['search' => $search]]);

        $options = [];

        foreach ($this->dispatchedBrowserEvents($tester, 'sylament:schema-component-method-result') as $payload) {
            foreach (is_array($payload['result'] ?? null) ? $payload['result'] : [] as $option) {
                if (is_array($option) && is_scalar($option['value'] ?? null) && is_scalar($option['label'] ?? null)) {
                    $options[(string) $option['value']] = (string) $option['label'];
                }
            }
        }

        return $options;
    }
}

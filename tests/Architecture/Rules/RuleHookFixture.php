<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Rules;

use RuntimeException;
use Sylament\Tests\Infrastructure\WorkerDirectory;
use UnexpectedValueException;

/**
 * Builds the projects and the hook inputs the rule hook's cases run against.
 */
final class RuleHookFixture
{
    public static function repositoryRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @param array<string, string> $files content keyed by path, tracked in a fresh git repository
     */
    public static function project(array $files): string
    {
        $root = WorkerDirectory::path() . '/' . uniqid('rule-hook-', true);

        foreach ($files as $path => $content) {
            if (!is_dir(dirname($root . '/' . $path))) {
                mkdir(dirname($root . '/' . $path), recursive: true);
            }

            file_put_contents($root . '/' . $path, $content);
        }

        exec('git -C ' . escapeshellarg($root) . ' init -q && git -C ' . escapeshellarg($root) . ' add -A', result_code: $status);

        if (0 !== $status) {
            throw new RuntimeException('git could not track the rule hook fixture.');
        }

        return (string) realpath($root);
    }

    /**
     * @param list<string> $paths
     */
    public static function rule(array $paths, string $body): string
    {
        return "---\npaths:\n" . implode('', array_map(static fn (string $path): string => "  - \"{$path}\"\n", $paths)) . "description: A rule.\n---\n" . $body;
    }

    /**
     * @param array<string, mixed>|null $response tool_response fields over those of a command that printed nothing, or null for no tool_response
     *
     * @return array<string, mixed>
     */
    public static function bash(string $command, string $cwd, ?string $agent = null, string $event = 'PostToolUse', ?array $response = []): array
    {
        $input = ['session_id' => 'session', 'hook_event_name' => $event, 'tool_name' => 'Bash', 'tool_input' => ['command' => $command], 'cwd' => $cwd];
        $input = null === $response ? $input : [...$input, 'tool_response' => [...['stdout' => '', 'stderr' => '', 'interrupted' => false, 'isImage' => false, 'noOutputExpected' => false], ...$response]];

        return null === $agent ? $input : [...$input, 'agent_id' => $agent];
    }

    /**
     * @return array<string, mixed>
     */
    public static function nativeLoad(string $ruleFile, ?string $agent = null): array
    {
        $input = ['session_id' => 'session', 'hook_event_name' => 'InstructionsLoaded', 'file_path' => $ruleFile, 'memory_type' => 'Project', 'load_reason' => 'path_glob_match', 'cwd' => '/'];

        return null === $agent ? $input : [...$input, 'agent_id' => $agent];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return string|null the context the hook adds, or null when it adds none
     */
    public static function context(RuleHook $hook, array $input): ?string
    {
        $output = $hook->handle($input);

        if (null === $output) {
            return null;
        }

        $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        $specific = is_array($decoded) ? $decoded['hookSpecificOutput'] ?? null : null;

        if (!is_array($specific) || ($specific['hookEventName'] ?? null) !== $input['hook_event_name'] || !is_string($specific['additionalContext'] ?? null)) {
            throw new UnexpectedValueException('The rule hook answered with output that is not the context for the event it was given: ' . $output);
        }

        return $specific['additionalContext'];
    }
}

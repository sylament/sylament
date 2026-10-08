<?php

declare(strict_types=1);

/*
 * Usage: registered in .claude/settings.json for PostToolUse and PostToolUseFailure on Bash, InstructionsLoaded
 * and PostCompact; Claude Code runs it with the hook input on STDIN.
 *
 * Prints the hook's JSON output when a Bash command touched files a rule covers that the agent has not loaded.
 * Exits 2 with the reason on STDERR when the input or a rule file cannot be read, which Claude Code shows the
 * agent after a Bash command.
 */

use Sylament\Schemas\Tests\Architecture\Rules\RuleHook;

$root = dirname(__DIR__, 5);

require $root . '/vendor/autoload.php';

try {
    $input = json_decode((string) stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);

    if (!is_array($input)) {
        throw new UnexpectedValueException('the hook input is not a JSON object');
    }

    $output = new RuleHook($root, sys_get_temp_dir() . '/sylament-rule-hook')->handle($input);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'The rule hook failed: ' . $throwable->getMessage() . "\n");

    exit(2);
}

if (null !== $output) {
    echo $output;
}

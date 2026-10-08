<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture;

use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Sylament\Schemas\Tests\Architecture\Rules\RuleHook;
use Symfony\Component\Yaml\Yaml;

use function dirname;

/**
 * The rule hook loads a path-scoped rule when a Bash command touches a file the rule covers, which Claude Code
 * itself does only for the Read, Edit and Write tools and plain single-file views; the reasoning is in
 * docs/internals/agent-instructions.md.
 */
function ruleHookRepositoryRoot(): string
{
    return dirname(__DIR__, 4);
}

/**
 * @param array<string, string> $files content keyed by path, tracked in a fresh git repository
 */
function ruleHookProject(array $files): string
{
    $root = sys_get_temp_dir() . '/' . uniqid('rule-hook-', true);

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

function ruleHookRemoveTree(string $root): void
{
    if (!is_dir($root)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }

    rmdir($root);
}

/**
 * @param list<string> $paths
 */
function ruleHookRule(array $paths, string $body): string
{
    return "---\npaths:\n" . implode('', array_map(static fn (string $path): string => "  - \"{$path}\"\n", $paths)) . "description: A rule.\n---\n" . $body;
}

/**
 * @return array<string, mixed>
 */
function ruleHookBash(string $command, string $cwd, ?string $agent = null, string $event = 'PostToolUse'): array
{
    $input = ['session_id' => 'session', 'hook_event_name' => $event, 'tool_name' => 'Bash', 'tool_input' => ['command' => $command], 'cwd' => $cwd];

    return null === $agent ? $input : [...$input, 'agent_id' => $agent];
}

/**
 * @return array<string, mixed>
 */
function ruleHookNativeLoad(string $ruleFile, ?string $agent = null): array
{
    $input = ['session_id' => 'session', 'hook_event_name' => 'InstructionsLoaded', 'file_path' => $ruleFile, 'memory_type' => 'Project', 'load_reason' => 'path_glob_match', 'cwd' => '/'];

    return null === $agent ? $input : [...$input, 'agent_id' => $agent];
}

/**
 * @param array<string, mixed> $input
 *
 * @return string|null the context the hook adds, or null when it adds none
 */
function ruleHookContext(RuleHook $hook, array $input): ?string
{
    $output = $hook->handle($input);

    if (null === $output) {
        return null;
    }

    $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);

    expect($decoded['hookSpecificOutput']['hookEventName'])->toBe($input['hook_event_name']);

    return $decoded['hookSpecificOutput']['additionalContext'];
}

beforeEach(function (): void {
    $this->root = ruleHookProject([
        '.claude/rules/php.md' => ruleHookRule(['packages/*/src/**/*.php'], "# PHP rules\n\n- php rule\n"),
        '.claude/rules/tests.md' => ruleHookRule(['packages/*/tests/**'], "# Test rules\n\n- tests rule\n"),
        '.claude/rules/trail.md' => ruleHookRule(['/docs/progress/**'], "# Trail rules\n\n- trail rule\n"),
        '.claude/rules/manifests.md' => ruleHookRule(['/composer.json', 'packages/*/composer.json'], "# Manifest rules\n\n- manifests rule\n"),
        'composer.json' => "{}\n",
        'packages/forms/composer.json' => "{}\n",
        'packages/forms/src/Components/TextInput.php' => "<?php\n",
        'packages/forms/tests/Unit/TextInputTest.php' => "<?php\n",
        'packages/forms/assets/src/styles/input.css' => "a {}\n",
        'docs/progress/forms/text-input.md' => "# Text input\n",
    ]);
    $this->hook = new RuleHook($this->root, $this->root . '-store');
});

afterEach(function (): void {
    ruleHookRemoveTree($this->root);
    ruleHookRemoveTree($this->root . '-store');
});

it('loads the rule of a file the command touches', function (string $command): void {
    $context = ruleHookContext($this->hook, ruleHookBash(str_replace('{root}', $this->root, $command), $this->root));

    expect($context)->toContain('- php rule')->toContain('.claude/rules/php.md');
})->with([
    'a piped read' => ['cat packages/forms/src/Components/TextInput.php | head -5'],
    'a quoted path' => ['grep -n "make" "packages/forms/src/Components/TextInput.php" | head'],
    'a redirected read' => ['cat packages/forms/src/Components/TextInput.php 2>/dev/null'],
    'an absolute path' => ["sed -n '1,5p' {root}/packages/forms/src/Components/TextInput.php | cat"],
    'a script' => ["python3 -c \"print(open('packages/forms/src/Components/TextInput.php').read())\""],
    'an in-place write' => ["sed -i '' 's/a/b/' packages/forms/src/Components/TextInput.php"],
    'a new file' => ["cat > packages/forms/src/Components/Toggle.php <<'EOF'\n<?php\nEOF"],
    "a directory at the rule's fixed prefix" => ['grep -rn make packages/forms/src | head'],
    'a wildcard path' => ['grep -rn make packages/*/src | head'],
]);

it('loads every rule the command touches', function (): void {
    $context = ruleHookContext($this->hook, ruleHookBash('grep -n make packages/forms/src/Components/TextInput.php docs/progress/forms/text-input.md', $this->root));

    expect($context)->toContain('- php rule')->toContain('- trail rule');
});

it('resolves a relative path against the cd before it', function (): void {
    $context = ruleHookContext($this->hook, ruleHookBash('cd ' . $this->root . '/packages/forms && grep -n make src/Components/TextInput.php | head', '/'));

    expect($context)->toContain('- php rule');
});

it('reads a dot as the directory it stands for', function (): void {
    $context = ruleHookContext($this->hook, ruleHookBash('grep -rn make . | head', $this->root . '/packages/forms/src'));

    expect($context)->toContain('- php rule');
});

it('matches a root-anchored rule', function (): void {
    expect(ruleHookContext($this->hook, ruleHookBash('cat docs/progress/forms/text-input.md | head', $this->root)))->toContain('- trail rule');
});

it('matches a file-shaped glob through its file', function (): void {
    expect(ruleHookContext($this->hook, ruleHookBash('cat packages/forms/composer.json | head', $this->root)))->toContain('- manifests rule');
});

it("loads nothing for a command that touches no rule's files", function (string $command): void {
    expect(ruleHookContext($this->hook, ruleHookBash($command, $this->root)))->toBeNull();
})->with([
    'a directory above every fixed prefix' => ['grep -rn make packages/forms | head'],
    "a directory holding none of the rule's files" => ['grep -rn color packages/forms/assets/src/styles'],
    'a path outside the project' => ['cat /elsewhere/packages/forms/src/Components/TextInput.php | head'],
    'a path that climbs out of the project' => ['cat ../lane/packages/forms/src/Components/TextInput.php | head'],
    'no path at all' => ['git status --short'],
]);

it('loads a rule once per agent', function (): void {
    $input = ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root);
    ruleHookContext($this->hook, $input);

    expect(ruleHookContext($this->hook, $input))->toBeNull();
});

it('loads a rule again for a subagent', function (): void {
    ruleHookContext($this->hook, ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root));

    expect(ruleHookContext($this->hook, ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root, agent: 'a1')))->toContain('- php rule');
});

it('skips a rule Claude Code already loaded for the agent', function (): void {
    $this->hook->handle(ruleHookNativeLoad($this->root . '/.claude/rules/php.md'));

    expect(ruleHookContext($this->hook, ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root)))->toBeNull();
});

it("ignores an instruction file outside the project's rules", function (): void {
    $this->hook->handle(ruleHookNativeLoad('/elsewhere/.claude/rules/php.md'));

    expect(ruleHookContext($this->hook, ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root)))->toContain('- php rule');
});

it('loads a rule again after its agent compacts', function (): void {
    $input = ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root);
    ruleHookContext($this->hook, $input);

    $this->hook->handle(['session_id' => 'session', 'hook_event_name' => 'PostCompact', 'trigger' => 'auto', 'compact_summary' => '', 'cwd' => $this->root]);

    expect(ruleHookContext($this->hook, $input))->toContain('- php rule');
});

it('answers a failed command as well', function (): void {
    expect(ruleHookContext($this->hook, ruleHookBash('cat packages/forms/src/Components/TextInput.php | head', $this->root, event: 'PostToolUseFailure')))->toContain('- php rule');
});

it('points to a rule that does not fit beside another, and loads it with the next command that touches it', function (): void {
    $root = ruleHookProject([
        '.claude/rules/php.md' => ruleHookRule(['packages/*/src/**/*.php'], str_repeat("- php rule\n", 550)),
        '.claude/rules/tests.md' => ruleHookRule(['packages/*/tests/**'], str_repeat("- tests rule\n", 500)),
        'packages/forms/src/X.php' => "<?php\n",
        'packages/forms/tests/XTest.php' => "<?php\n",
    ]);
    $hook = new RuleHook($root, $root . '-store');

    $first = ruleHookContext($hook, ruleHookBash('grep -n x packages/forms/src/X.php packages/forms/tests/XTest.php', $root));
    $second = ruleHookContext($hook, ruleHookBash('cat packages/forms/tests/XTest.php | head', $root));

    ruleHookRemoveTree($root);
    ruleHookRemoveTree($root . '-store');

    expect($first)->toContain('- php rule')->not->toContain('- tests rule')->toContain('.claude/rules/tests.md')
        ->and(mb_strlen((string) $first))->toBeLessThanOrEqual(10_000)
        ->and($second)->toContain('- tests rule');
});

it('refuses a tool other than Bash', function (): void {
    $this->hook->handle(['session_id' => 'session', 'hook_event_name' => 'PostToolUse', 'tool_name' => 'Read', 'tool_input' => ['file_path' => 'composer.json'], 'cwd' => $this->root]);
})->throws(LogicException::class);

it('refuses an event it is not registered for', function (): void {
    $this->hook->handle(['session_id' => 'session', 'hook_event_name' => 'Stop', 'cwd' => $this->root]);
})->throws(LogicException::class);

it('delivers every rule of this repository whole through a Bash read of a file it covers', function (): void {
    $root = ruleHookRepositoryRoot();
    $store = sys_get_temp_dir() . '/' . uniqid('rule-hook-store-', true);
    $hook = new RuleHook($root, $store);
    $rules = glob($root . '/.claude/rules/*.md') ?: [];
    exec('git -C ' . escapeshellarg($root) . ' ls-files', $tracked);
    $undelivered = [];

    foreach ($rules as $rule) {
        $content = (string) file_get_contents($rule);
        preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $content, $parts);
        $globs = Yaml::parse($parts[1])['paths'];
        $covered = array_values(array_filter($tracked, static fn (string $file): bool => array_any($globs, static fn (string $glob): bool => RuleHook::globMatches($glob, $file))));
        $agent = basename($rule, '.md');

        foreach ($rules as $other) {
            if ($other !== $rule) {
                $hook->handle(ruleHookNativeLoad($other, $agent));
            }
        }

        $context = [] === $covered ? null : ruleHookContext($hook, ruleHookBash("cat {$covered[0]} | head -1", $root, $agent));

        if (!str_contains((string) $context, mb_trim($parts[2]))) {
            $undelivered[] = basename($rule) . ' (' . mb_strlen($parts[2]) . ' characters)';
        }
    }

    ruleHookRemoveTree($store);

    expect($undelivered)->toBe([], 'The rule hook could not deliver these whole: a rule must fit within ' . RuleHook::CONTEXT_LIMIT . ' characters with its header, so split it by paths: ' . implode(', ', $undelivered));
});

it('runs as a command and reports a failure on STDERR', function (): void {
    $script = ruleHookRepositoryRoot() . '/packages/schemas/tests/Architecture/Rules/rule-hook.php';
    $run = static function (string $stdin) use ($script): array {
        $process = proc_open(['php', $script], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);

        return [$stdout, $stderr, proc_close($process)];
    };
    $session = uniqid('rule-hook-command-', false);

    [$stdout, , $status] = $run((string) json_encode([...ruleHookBash('cat packages/forms/src/Components/TextInput.php | head -1', ruleHookRepositoryRoot()), 'session_id' => $session]));
    [, $failureStderr, $failureStatus] = $run('not json');

    $state = sys_get_temp_dir() . "/sylament-rule-hook/{$session}.main.json";

    if (is_file($state)) {
        unlink($state);
    }

    expect($status)->toBe(0)
        ->and(json_decode($stdout, true, flags: JSON_THROW_ON_ERROR)['hookSpecificOutput']['additionalContext'])->toContain('.claude/rules/php.md')
        ->and($failureStatus)->toBe(2)
        ->and($failureStderr)->toContain('rule hook');
});

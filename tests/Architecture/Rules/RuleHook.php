<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Rules;

use Closure;
use LogicException;
use RuntimeException;
use Sylament\Tests\Architecture\TrackedFiles;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Glob;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * Loads a path-scoped rule from .claude/rules/ into an agent's context when a Bash command touches a file the
 * rule covers. Claude Code loads a rule itself only for the Read, Edit and Write tools and a plain single-file
 * cat, head, tail, sed -n or grep, while agents mostly read through piped, multi-file and cd-prefixed commands;
 * the reasoning and the wiring are in docs/internals/agent-instructions.md.
 */
final class RuleHook
{
    /**
     * Claude Code caps a hook's additionalContext at 10,000 characters and replaces a longer one with a file
     * path and a 2,000-character preview, so a rule reaches the agent whole only within this limit.
     */
    public const int CONTEXT_LIMIT = 9_900;

    /**
     * @var list<string>|null
     */
    private ?array $trackedFiles = null;

    public function __construct(
        private readonly string $root,
        private readonly string $stateDirectory,
    ) {
    }

    public static function globMatches(string $glob, string $path): bool
    {
        return 1 === preg_match(Glob::toRegex(mb_ltrim($glob, '/'), strictLeadingDot: false), $path);
    }

    /**
     * @param array<mixed> $input the JSON Claude Code passes a hook on STDIN
     *
     * @return string|null the JSON to print, or null when the hook adds nothing
     */
    public function handle(array $input): ?string
    {
        return match ($input['hook_event_name'] ?? null) {
            'PostToolUse', 'PostToolUseFailure' => $this->afterCommand($input),
            'InstructionsLoaded' => $this->afterNativeLoad($input),
            'PostCompact' => $this->afterCompaction($input),
            default => throw new LogicException('The rule hook handles PostToolUse, PostToolUseFailure, InstructionsLoaded and PostCompact only; check its registration in .claude/settings.json.'),
        };
    }

    /**
     * @param array<mixed> $input
     */
    private function afterCommand(array $input): ?string
    {
        if ('Bash' !== ($input['tool_name'] ?? null)) {
            throw new LogicException('The rule hook reads Bash commands only; match it to Bash in .claude/settings.json.');
        }

        $toolInput = $input['tool_input'] ?? null;

        if (!is_array($toolInput)) {
            throw new UnexpectedValueException('The hook input lacks tool_input.');
        }

        $command = $this->field($toolInput, 'command');
        $cwd = $this->field($input, 'cwd');
        $rules = $this->rules();
        $output = 'PostToolUse' === $input['hook_event_name'] ? $this->outputShown($input) : null;
        $read = null === $output ? [] : $this->rulesPrintedWhole($rules, $command, $cwd, $output);
        $touched = $this->rulesTouchedBy($rules, $command, $cwd);

        if ([] === $touched && [] === $read) {
            return null;
        }

        $context = $this->withState($input, fn (array $state): array => $this->contextFor([...$state, ...array_fill_keys($read, 'loaded')], $touched));

        if ('' === $context) {
            return null;
        }

        return json_encode(
            ['hookSpecificOutput' => ['hookEventName' => $input['hook_event_name'], 'additionalContext' => $context]],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * @param array<mixed> $input
     */
    private function afterNativeLoad(array $input): null
    {
        $file = $this->relative($this->field($input, 'file_path'));

        if (null !== $file && str_starts_with($file, '.claude/rules/')) {
            $this->withState($input, static fn (array $state): array => [[...$state, $file => 'loaded'], null]);
        }

        return null;
    }

    /**
     * @param array<mixed> $input
     */
    private function afterCompaction(array $input): null
    {
        new Filesystem()->remove($this->statePath($input));

        return null;
    }

    /**
     * @param array<string, string> $state 'loaded' or 'pointed', keyed by rule file
     * @param list<array{file: string, body: string}> $rules
     *
     * @return array{array<string, string>, string} the new state and the context to add
     */
    private function contextFor(array $state, array $rules): array
    {
        $new = array_values(array_filter($rules, static fn (array $rule): bool => 'loaded' !== ($state[$rule['file']] ?? null)));
        $texts = [];
        $deferred = [];

        foreach ($new as $index => $rule) {
            $text = "Contents of {$rule['file']} (loaded by the project's rule hook, because your last command touched files it covers):\n\n{$rule['body']}";
            $later = array_column(array_slice($new, $index + 1), 'file');

            if (mb_strlen(implode("\n\n", [...$texts, $text, $this->pointer([...$deferred, ...$later])])) <= self::CONTEXT_LIMIT) {
                $texts[] = $text;
                $state[$rule['file']] = 'loaded';
            } else {
                $deferred[] = $rule['file'];
            }
        }

        $unpointed = array_values(array_filter($deferred, static fn (string $file): bool => 'pointed' !== ($state[$file] ?? null)));

        foreach ($deferred as $file) {
            $state[$file] = 'pointed';
        }

        return [$state, implode("\n\n", array_filter([...$texts, $this->pointer($unpointed)]))];
    }

    /**
     * @param list<string> $files
     */
    private function pointer(array $files): string
    {
        if ([] === $files) {
            return '';
        }

        return 'Run `cat ' . implode(' ', $files) . '` as well: ' . (1 === count($files) ? 'it covers' : 'they cover') . ' files your last command touched and did not fit in this message.';
    }

    /**
     * @param array<mixed> $input
     *
     * @return string|null the command's output, or null when Claude Code showed the agent only a preview of it or the input lacks it
     */
    private function outputShown(array $input): ?string
    {
        $response = $input['tool_response'] ?? null;

        if (!is_array($response) || isset($response['persistedOutputPath']) || !is_string($response['stdout'] ?? null)) {
            return null;
        }

        return $response['stdout'];
    }

    /**
     * A cat segment prints a file whole unless a pipe or a substitution feeds it or takes its output, it
     * redirects its output elsewhere, or a backslash continues it onto the next line; head, tail, grep and sed -n
     * print a part. A command with a heredoc counts nothing, since its body lines only look like commands. A
     * rule counts only when its text is in the output, which a group, a substitution or a skipped short-circuit
     * around the cat keeps out.
     *
     * @param list<array{file: string, globs: list<string>, body: string}> $rules
     *
     * @return list<string> the rule files the command printed whole
     */
    private function rulesPrintedWhole(array $rules, string $command, string $cwd, string $output): array
    {
        if (str_contains($command, '<<')) {
            return [];
        }

        $bodies = array_column($rules, 'body', 'file');
        $printed = [];

        foreach ($this->segments($command, $cwd) as $segment) {
            $redirects = (string) preg_replace('/(?<!\S)2>(?:&1|\S+)/', '', $segment['text']);

            if ('cat' !== ($segment['words'][0] ?? null) || in_array($segment['before'], ['|', '('], true) || in_array($segment['after'], ['|', ')'], true) || 1 === preg_match('/[<>]|\\\\\s*$/', $redirects)) {
                continue;
            }

            foreach (array_slice($segment['words'], 1) as $word) {
                $file = $this->relative($this->resolve($segment['directory'], $word));

                if (null !== $file && isset($bodies[$file]) && str_contains($output, $bodies[$file])) {
                    $printed[] = $file;
                }
            }
        }

        return array_values(array_unique($printed));
    }

    /**
     * @param list<array{file: string, globs: list<string>, body: string}> $rules
     *
     * @return list<array{file: string, body: string}> the rules covering a path the command names, in the order it names them
     */
    private function rulesTouchedBy(array $rules, string $command, string $cwd): array
    {
        $touched = [];

        foreach ($this->pathsIn($command, $cwd) as $path) {
            foreach ($rules as $rule) {
                if (!isset($touched[$rule['file']]) && $this->covers($rule['globs'], $path)) {
                    $touched[$rule['file']] = ['file' => $rule['file'], 'body' => $rule['body']];
                }
            }
        }

        return array_values($touched);
    }

    /**
     * @return list<array{file: string, globs: list<string>, body: string}> every rule scoped by paths:, which Claude Code would load lazily
     */
    private function rules(): array
    {
        $directory = $this->root . '/.claude/rules';

        if (!is_dir($directory)) {
            return [];
        }

        $rules = [];

        foreach (Finder::create()->files()->in($directory)->name('*.md')->sortByName() as $file) {
            if (1 !== preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $file->getContents(), $parts)) {
                continue;
            }

            $frontmatter = Yaml::parse($parts[1]);
            $paths = is_array($frontmatter) ? ($frontmatter['paths'] ?? null) : null;

            if (null === $paths) {
                continue;
            }

            if (!is_array($paths) || !array_is_list($paths)) {
                throw new UnexpectedValueException("{$file->getPathname()}: paths must be a list of globs.");
            }

            $globs = [];

            foreach ($paths as $glob) {
                if (!is_string($glob)) {
                    throw new UnexpectedValueException("{$file->getPathname()}: paths must be a list of globs.");
                }

                $globs[] = $glob;
            }

            $rules[] = ['file' => '.claude/rules/' . $file->getRelativePathname(), 'globs' => $globs, 'body' => mb_trim($parts[2])];
        }

        return $rules;
    }

    /**
     * @return list<string> the paths inside the project the command names, relative to the root, in order
     */
    private function pathsIn(string $command, string $cwd): array
    {
        $paths = [];

        foreach ($this->segments($command, $cwd) as $segment) {
            $directory = $segment['directory'];

            foreach ($segment['words'] as $word) {
                $word = (string) preg_replace('/:\d+(?::\d+)?$/', '', $word);
                $word = str_contains($word, ':') ? mb_substr($word, (int) mb_strrpos($word, ':') + 1) : $word;

                if ('.' !== $word && !str_contains($word, '/') && 1 !== preg_match('/^[\w.-]+\.\w+$/', $word)) {
                    continue;
                }

                $path = $this->relative($this->resolve($directory, $word));

                if (null !== $path && '' !== $path) {
                    $paths[] = $path;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * The command's segments other than cd, each with the directory a cd before it moved to and the separators
     * around it.
     *
     * @return list<array{directory: string, words: list<string>, text: string, before: string, after: string}>
     */
    private function segments(string $command, string $cwd): array
    {
        $parts = preg_split('/(&&|\|\||[;|()\n])/', $command, flags: PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $segments = [];
        $directory = $cwd;

        foreach ($parts as $index => $text) {
            if (1 === $index % 2) {
                continue;
            }

            $words = preg_split('/[\s\'"`<>=]+/', $text, flags: PREG_SPLIT_NO_EMPTY) ?: [];

            if ('cd' === ($words[0] ?? null)) {
                $directory = isset($words[1]) ? $this->resolve($directory, $words[1]) : $directory;

                continue;
            }

            $segments[] = ['directory' => $directory, 'words' => $words, 'text' => $text, 'before' => $parts[$index - 1] ?? '', 'after' => $parts[$index + 1] ?? ''];
        }

        return $segments;
    }

    /**
     * A path with a wildcard, or one naming a directory, is an area: it touches a rule only at or below the
     * rule's fixed prefix (the segments before its first **) and only when it holds a tracked file the rule
     * covers, so a search over a whole package does not load every rule of the package.
     *
     * @param list<string> $globs
     */
    private function covers(array $globs, string $path): bool
    {
        $area = 1 === preg_match('/[*?\[{]/', $path) || is_dir($this->root . '/' . $path);
        return array_any($globs, fn (string $glob): bool => $area ? $this->reaches($glob, $path) : self::globMatches($glob, $path));
    }

    private function reaches(string $glob, string $area): bool
    {
        $fixed = array_find_key(explode('/', mb_ltrim($glob, '/')), static fn (string $segment): bool => str_contains($segment, '**'));

        if (null === $fixed || count(explode('/', $area)) < $fixed) {
            return false;
        }

        $itself = Glob::toRegex($area, strictLeadingDot: false);
        $below = Glob::toRegex($area . '/**', strictLeadingDot: false);

        return array_any(
            $this->trackedFiles(),
            static fn (string $file): bool => (1 === preg_match($below, $file) || 1 === preg_match($itself, $file)) && self::globMatches($glob, $file),
        );
    }

    /**
     * @return list<string>
     */
    private function trackedFiles(): array
    {
        return $this->trackedFiles ??= TrackedFiles::in($this->root);
    }

    private function relative(string $path): ?string
    {
        if ($path === $this->root) {
            return '';
        }

        return str_starts_with($path, $this->root . '/') ? mb_substr($path, mb_strlen($this->root) + 1) : null;
    }

    private function resolve(string $directory, string $path): string
    {
        $absolute = match (true) {
            str_starts_with($path, '/') => $path,
            str_starts_with($path, '~') => getenv('HOME') . mb_substr($path, 1),
            default => $directory . '/' . $path,
        };
        $segments = [];

        foreach (explode('/', $absolute) as $segment) {
            if ('..' === $segment) {
                array_pop($segments);
            } elseif ('' !== $segment && '.' !== $segment) {
                $segments[] = $segment;
            }
        }

        return '/' . implode('/', $segments);
    }

    /**
     * @template T
     *
     * @param array<mixed> $input
     * @param Closure(array<string, string>): array{array<string, string>, T} $change
     *
     * @return T
     */
    private function withState(array $input, Closure $change): mixed
    {
        new Filesystem()->mkdir($this->stateDirectory);
        $handle = fopen($this->statePath($input), 'c+');

        if (false === $handle) {
            throw new RuntimeException("The rule hook cannot open its state in {$this->stateDirectory}.");
        }

        try {
            flock($handle, LOCK_EX);
            $json = (string) stream_get_contents($handle);
            $stored = '' === $json ? [] : json_decode($json, true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($stored)) {
                throw new UnexpectedValueException("The rule hook's state in {$this->stateDirectory} is corrupt.");
            }

            $state = [];

            foreach ($stored as $file => $status) {
                if (!is_string($file) || !is_string($status)) {
                    throw new UnexpectedValueException("The rule hook's state in {$this->stateDirectory} is corrupt.");
                }

                $state[$file] = $status;
            }

            [$state, $result] = $change($state);
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param array<mixed> $input
     */
    private function statePath(array $input): string
    {
        $agent = isset($input['agent_id']) ? $this->field($input, 'agent_id') : 'main';
        $name = preg_replace('/[^\w-]/', '', $this->field($input, 'session_id')) . '.' . preg_replace('/[^\w-]/', '', $agent);

        return "{$this->stateDirectory}/{$name}.json";
    }

    /**
     * @param array<mixed> $input
     */
    private function field(array $input, string $key): string
    {
        $value = $input[$key] ?? null;

        if (!is_string($value) || '' === $value) {
            throw new UnexpectedValueException("The hook input lacks {$key}.");
        }

        return $value;
    }
}

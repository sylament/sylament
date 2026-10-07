<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Architecture\Trail;

use Closure;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

/**
 * The port trail under docs/progress/ as data: it parses detail files and package READMEs, rolls row
 * statuses up into file and package statuses, renders and writes the generated README indexes, and
 * lists what TrailFilesTest rejects. The rules are in .claude/rules/trail.md and their reasoning in
 * docs/internals/trail.md.
 */
final class Trail
{
    public const string INDEX_START = '<!-- trail-index:start -->';

    public const string INDEX_END = '<!-- trail-index:end -->';

    public const int SIZE_ALARM_BYTES = 90 * 1024;

    private const array HEADERS = [
        'Surface' => ['Feature', 'Ports', 'Status', 'Blocked by', 'Detail'],
        'Unported' => ['Filament', 'Status', 'Blocked by', 'Detail'],
    ];

    private const array UNAUDITED = ['_not audited yet_', '_rest not audited yet_'];

    private const string DETAIL_CELL = '/^(—|\[[^\]]+\]\([^)\s]+\)( · \[[^\]]+\]\([^)\s]+\))*)$/u';

    private const string OWNABLE = '#^packages/[^/]+/(?:src|templates|assets/src)/#';

    private const string REFERENCE = '#(docs/progress/(?:[\w-]+/)*[\w.-]+\.md)(?:`?(?:,\s*(?:section\s+)?|\s+section\s+|\s*\()"([^"]+)")?#u';

    /**
     * @param list<string>|null $tracked the repository's tracked files; null reads them through git ls-files
     */
    public function __construct(
        private readonly string $root,
        private ?array $tracked = null,
    ) {
    }

    /**
     * @param list<Status> $statuses
     */
    public static function rollup(array $statuses, bool $unaudited): Status
    {
        if ([] === $statuses) {
            return $unaudited ? Status::Todo : throw new UnexpectedValueException('has no Surface row');
        }

        $only = static fn (Status ...$allowed): bool => [] === array_diff(
            array_map(static fn (Status $status): string => $status->value, $statuses),
            array_map(static fn (Status $status): string => $status->value, $allowed),
        );
        $has = static fn (Status $status): bool => in_array($status, $statuses, true);

        return match (true) {
            !$unaudited && $only(Status::Skipped) => Status::Skipped,
            !$unaudited && $only(Status::Done, Status::Skipped) => Status::Done,
            $only(Status::Todo) => Status::Todo,
            !$has(Status::Done) && !$has(Status::InProgress) => $has(Status::Blocked) ? Status::Blocked : Status::Todo,
            default => Status::InProgress,
        };
    }

    /**
     * The tables under every "## <sectionPrefix>…" heading; a "###" inside the section does not end it.
     *
     * @param 'Surface'|'Unported' $sectionPrefix
     *
     * @return list<Table>
     *
     * @throws UnexpectedValueException listing every malformed table, row and cell, each as "<line>: <problem>"
     */
    public static function tables(string $markdown, string $sectionPrefix): array
    {
        ['tables' => $tables, 'violations' => $violations] = self::scanTables($markdown, $sectionPrefix);

        if ([] !== $violations) {
            throw new UnexpectedValueException(implode("\n", $violations));
        }

        return $tables;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws UnexpectedValueException when the file has no frontmatter, a YAML comment, or YAML that is not a mapping
     */
    public static function frontmatter(string $markdown): array
    {
        if (1 !== preg_match('/\A---\n(.*?)\n---\n/s', $markdown, $match)) {
            throw new UnexpectedValueException('has no frontmatter (a --- block on its first line)');
        }

        foreach (explode("\n", $match[1]) as $offset => $line) {
            if (self::isYamlComment($line)) {
                throw new UnexpectedValueException(sprintf('line %d: the frontmatter holds a YAML comment, which DocumentationFilesTest reads as a heading', $offset + 2));
            }
        }

        try {
            $parsed = Yaml::parse($match[1]);
        } catch (ParseException $parseException) {
            throw new UnexpectedValueException('the frontmatter is not valid YAML: ' . $parseException->getMessage(), $parseException->getCode(), previous: $parseException);
        }

        if (!is_array($parsed)) {
            throw new UnexpectedValueException('the frontmatter is not a YAML mapping');
        }

        $frontmatter = [];

        foreach ($parsed as $key => $value) {
            if (!is_string($key)) {
                throw new UnexpectedValueException('the frontmatter is not a YAML mapping');
            }

            $frontmatter[$key] = $value;
        }

        return $frontmatter;
    }

    /**
     * @param array<string, mixed> $frontmatter
     *
     * @return list<string>
     */
    public function frontmatterViolations(array $frontmatter): array
    {
        $keys = ['description', 'ports', 'owns'];
        $violations = [];
        $missing = array_diff($keys, array_keys($frontmatter));
        $extra = array_diff(array_keys($frontmatter), $keys);

        if ([] !== $missing) {
            $violations[] = 'the frontmatter lacks ' . implode(', ', $missing);
        }

        if ([] !== $extra) {
            $violations[] = 'the frontmatter holds only description, ports and owns, not ' . implode(', ', $extra);
        }

        $description = $frontmatter['description'] ?? '';

        if (array_key_exists('description', $frontmatter) && (!is_string($description) || '' === mb_trim($description) || str_contains($description, "\n"))) {
            $violations[] = 'description is one non-empty line';
        }

        foreach (['ports', 'owns'] as $key) {
            if (array_key_exists($key, $frontmatter) && !self::isStringList($frontmatter[$key])) {
                $violations[] = "{$key} is a list of paths, [] when empty";
            }
        }

        $ports = $frontmatter['ports'] ?? [];

        foreach (self::isStringList($ports) ? $ports : [] as $port) {
            $absolute = $this->root . '/' . mb_rtrim($port, '/');

            $violation = match (true) {
                !str_starts_with($port, 'vendor/filament/') => 'is not under vendor/filament/',
                !file_exists($absolute) => 'does not exist',
                is_dir($absolute) && !str_ends_with($port, '/') => 'is a directory, so it ends with /',
                !is_dir($absolute) && str_ends_with($port, '/') => 'is a file, so it does not end with /',
                default => null,
            };

            if (null !== $violation) {
                $violations[] = "ports entry {$port} {$violation}";
            }
        }

        return $violations;
    }

    /**
     * The row rules every Surface and Unported row follows.
     *
     * @return list<string> each as "<line>: <problem>"
     */
    public static function rowViolations(Row $row): array
    {
        $violations = [];

        if (Status::Blocked === $row->status && in_array($row->blockedBy, ['', '—'], true)) {
            $violations[] = "{$row->line}: a blocked row names its prerequisite in Blocked by";
        }

        if (Status::Blocked !== $row->status && '—' !== $row->blockedBy) {
            $violations[] = "{$row->line}: a row that is {$row->status->value} has — in Blocked by, not \"{$row->blockedBy}\"";
        }

        if (1 !== preg_match(self::DETAIL_CELL, $row->detail)) {
            $violations[] = "{$row->line}: the Detail cell holds only links joined by \" · \", or —, not \"{$row->detail}\"";
        }

        return $violations;
    }

    /**
     * @param array{inProgress: int, blocked: int, todo: int, unaudited: bool} $open
     */
    public static function renderOpen(array $open): string
    {
        $parts = [];

        foreach (['inProgress' => 'in progress', 'blocked' => 'blocked', 'todo' => 'todo'] as $key => $label) {
            if (0 < $open[$key]) {
                $parts[] = "{$open[$key]} {$label}";
            }
        }

        if ($open['unaudited']) {
            $parts[] = 'unaudited';
        }

        return [] === $parts ? '—' : implode(' · ', $parts);
    }

    /**
     * @param list<string> $ports
     */
    public static function renderPorts(array $ports, string $package): string
    {
        if ([] === $ports) {
            return '—';
        }

        $prefix = 'vendor/filament/' . self::vendorPackage($package) . '/';
        $names = [];

        foreach ($ports as $port) {
            $name = str_starts_with($port, $prefix) ? (string) preg_replace('#^src/#', '', mb_substr($port, mb_strlen($prefix))) : $port;
            $names[] = '`' . ('' === $name ? $port : $name) . '`';
        }

        return implode(', ', $names);
    }

    public static function vendorPackage(string $package): string
    {
        return 'panels' === $package ? 'filament' : $package;
    }

    /**
     * @return array<string, list<DetailFile>> keyed by package, each list sorted by file name
     *
     * @throws UnexpectedValueException when a detail file's frontmatter or tables cannot be read
     */
    public function detailFiles(): array
    {
        $files = [];

        foreach ($this->packages() as $package) {
            $files[$package] = $this->packageFiles($package);
        }

        return $files;
    }

    /**
     * @return list<Row>
     *
     * @throws UnexpectedValueException when the package README has no well-formed Unported table
     */
    public function unported(string $package): array
    {
        $rows = [];

        foreach ($this->unportedTables($package) as $table) {
            array_push($rows, ...$table->rows);
        }

        return $rows;
    }

    /**
     * @throws UnexpectedValueException when a file of the package cannot be read or the package has nothing to roll up
     */
    public function packageStatus(string $package): Status
    {
        $statuses = array_map(static fn (DetailFile $file): Status => $file->status(), $this->packageFiles($package));
        $unaudited = false;

        foreach ($this->unportedTables($package) as $table) {
            array_push($statuses, ...array_map(static fn (Row $row): Status => $row->status, $table->rows));
            $unaudited = $unaudited || $table->unaudited;
        }

        try {
            return self::rollup($statuses, $unaudited);
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new UnexpectedValueException("{$this->readme($package)}: the package has no detail file and no Unported row", $unexpectedValueException->getCode(), previous: $unexpectedValueException);
        }
    }

    public function renderPackageIndex(string $package): string
    {
        $lines = ['| File | Status | Open | Ports | Description |', '| --- | --- | --- | --- | --- |'];

        foreach ($this->packageFiles($package) as $file) {
            $name = basename($file->path);
            $lines[] = sprintf(
                '| [%s](%s) | %s | %s | %s | %s |',
                $name,
                $name,
                $file->status()->value,
                self::renderOpen($file->open()),
                self::renderPorts($file->ports, $package),
                str_replace('|', '\|', $file->description),
            );
        }

        return implode("\n", $lines);
    }

    public function renderRootIndex(): string
    {
        $lines = ['| Package | Ports | Status | Detail |', '| --- | --- | --- | --- |'];

        foreach ($this->packages() as $package) {
            $lines[] = sprintf(
                '| [`%1$s`](../../packages/%1$s) | [`vendor/filament/%2$s`](../../vendor/filament/%2$s) | %3$s | [%1$s](%1$s/README.md) |',
                $package,
                self::vendorPackage($package),
                $this->packageStatus($package)->value,
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Renders every index first and writes only once all of them render, so a failure leaves no README half-updated.
     *
     * @return list<string> the README paths whose block changed
     *
     * @throws UnexpectedValueException when a README lacks its markers or a trail file cannot be read
     */
    public function writeIndexes(): array
    {
        $updates = [];

        foreach ($this->indexes() as $path => $render) {
            $content = $this->read($path);
            $updated = self::replaceIndex($content, $path, $render());

            if ($updated !== $content) {
                $updates[$path] = $updated;
            }
        }

        foreach ($updates as $path => $content) {
            if (false === file_put_contents($this->root . '/' . $path, $content)) {
                throw new RuntimeException("Could not write {$path}.");
            }
        }

        return array_keys($updates);
    }

    /**
     * Check 6: every index block equals its rendering.
     *
     * @return list<string>
     */
    public function indexViolations(): array
    {
        $violations = [];

        foreach ($this->indexes() as $path => $render) {
            try {
                $content = $this->read($path);

                if (self::replaceIndex($content, $path, $render()) !== $content) {
                    $violations[] = "{$path}: the trail index is stale; run `composer docs:index`";
                }
            } catch (UnexpectedValueException $exception) {
                $violations[] = "{$path}: the trail index cannot be rendered: " . str_replace("\n", '; ', $exception->getMessage());
            }
        }

        return $violations;
    }

    /**
     * Check 2. An ownable file's owner is the trail file holding the longest entry that is the file itself or a
     * directory (ending in /) above it.
     *
     * @return array{owners: array<string, string>, violations: list<string>}
     */
    public function ownership(): array
    {
        $holders = [];
        $violations = [];

        foreach ($this->packages() as $package) {
            foreach ($this->detailPaths($package) as $path) {
                try {
                    $owns = self::frontmatter($this->read($path))['owns'] ?? null;
                } catch (UnexpectedValueException) {
                    $owns = null;
                }

                if (!self::isStringList($owns)) {
                    $violations[] = "unreadable owns: {$path} declares no owns list, so it owns nothing";

                    continue;
                }

                foreach (array_unique($owns) as $entry) {
                    if (1 !== preg_match(self::OWNABLE, $entry)) {
                        $violations[] = "not ownable: {$path}: {$entry} is outside packages/*/src/, templates/ and assets/src/";

                        continue;
                    }

                    $holders[$entry][] = $path;
                }
            }
        }

        $owners = [];
        $matched = [];

        foreach ($this->ownableFiles() as $file) {
            $longest = null;

            foreach (array_keys($holders) as $entry) {
                $entry = (string) $entry;

                if ($entry === $file || (str_ends_with($entry, '/') && str_starts_with($file, $entry))) {
                    $matched[$entry] = true;
                    $longest = null === $longest || mb_strlen($entry) > mb_strlen($longest) ? $entry : $longest;
                }
            }

            if (null === $longest) {
                $violations[] = "unowned: {$file}";
            } elseif (1 === count($holders[$longest])) {
                $owners[$file] = $holders[$longest][0];
            }
        }

        foreach ($holders as $entry => $paths) {
            if (1 < count($paths)) {
                $violations[] = "owned twice: {$entry} is in " . implode(', ', $paths);
            }

            if (!isset($matched[$entry])) {
                $hint = !str_ends_with($entry, '/') && is_dir($this->root . '/' . $entry) ? ' (a directory entry ends with /)' : '';

                foreach ($paths as $path) {
                    $violations[] = "stale entry: {$path}: {$entry} matches no tracked ownable file{$hint}";
                }
            }
        }

        return ['owners' => $owners, 'violations' => $violations];
    }

    /**
     * Checks 1, 3, 4 and 5: frontmatter, table and row rules, no Details in a settled file, no Status line.
     *
     * @return list<string>
     */
    public function formatViolations(): array
    {
        $violations = [];

        foreach ($this->packages() as $package) {
            foreach ($this->detailPaths($package) as $path) {
                ['file' => $file, 'violations' => $found] = $this->inspect($path, $package);
                array_push($violations, ...$found);

                if (null === $file || !in_array('Details', $file->sections, true)) {
                    continue;
                }

                try {
                    $status = $file->status();
                } catch (UnexpectedValueException) {
                    continue;
                }

                if (in_array($status, [Status::Done, Status::Skipped], true)) {
                    $violations[] = "{$path}: a {$status->value} file has no ## Details section";
                }
            }

            array_push($violations, ...$this->readmeViolations($package));
        }

        foreach ($this->trailPaths() as $path) {
            foreach (self::proseLines($this->read($path)) as $number => $line) {
                if (str_starts_with($line, '**Status:**')) {
                    $violations[] = "{$path}:{$number}: a **Status:** line; status lives on rows and the generated indexes";
                }
            }
        }

        return $violations;
    }

    /**
     * Check 7: every docs/progress path named in packages/**.php or docs/internals/*.md exists, and a quoted title
     * after it is contained in one of that file's headings, backticks ignored.
     *
     * @return list<string>
     */
    public function referenceViolations(): array
    {
        $violations = [];
        $sources = array_filter(
            $this->tracked(),
            static fn (string $path): bool => 1 === preg_match('#^packages/.+\.php$|^docs/internals/[^/]+\.md$#', $path),
        );

        foreach ($sources as $source) {
            $content = $this->read($source);
            preg_match_all(self::REFERENCE, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

            foreach ($matches as $match) {
                [$path, $offset] = $match[1];
                $at = $source . ':' . (mb_substr_count(mb_strcut($content, 0, $offset), "\n") + 1);

                if (null === $path) {
                    continue;
                }

                if (!is_file($this->root . '/' . $path)) {
                    $violations[] = "{$at}: names {$path}, which does not exist";

                    continue;
                }

                $title = isset($match[2][0]) ? self::normalizeTitle($match[2][0]) : null;

                if (null === $title) {
                    continue;
                }

                $headings = array_map(self::normalizeTitle(...), array_column(self::headings($this->read($path)), 'text'));

                if ([] === array_filter($headings, static fn (string $heading): bool => str_contains($heading, $title))) {
                    $violations[] = "{$at}: {$path} has no heading containing \"{$title}\"";
                }
            }
        }

        return $violations;
    }

    /**
     * Check 9: no trail file is past the size alarm, so every file can be read in one call.
     *
     * @return list<string>
     */
    public function sizeViolations(): array
    {
        $violations = [];

        foreach ($this->trailPaths() as $path) {
            $bytes = filesize($this->root . '/' . $path);

            if (false === $bytes) {
                throw new UnexpectedValueException("{$path} cannot be read");
            }

            if ($bytes > self::SIZE_ALARM_BYTES) {
                $violations[] = "{$path}: {$bytes} bytes, past the 90 KB alarm — split it along the code it owns";
            }
        }

        return $violations;
    }

    /**
     * @param 'Surface'|'Unported' $sectionPrefix
     *
     * @return array{tables: list<Table>, violations: list<string>}
     */
    private static function scanTables(string $markdown, string $sectionPrefix): array
    {
        $expected = self::HEADERS[$sectionPrefix];
        $tables = [];
        $violations = [];

        foreach (self::sections($markdown, $sectionPrefix) as $section) {
            $blocks = self::tableBlocks($section['lines']);

            if (1 !== count($blocks)) {
                $violations[] = "{$section['line']}: ## {$section['heading']} holds " . count($blocks) . ' tables; it holds exactly one';
            }

            foreach ($blocks as $block) {
                ['table' => $table, 'violations' => $found] = self::parseTable($block, $section['heading'], $expected);
                array_push($violations, ...$found);

                if (null !== $table) {
                    $tables[] = $table;
                }
            }
        }

        return ['tables' => $tables, 'violations' => $violations];
    }

    /**
     * Each "## <prefix>…" section with its prose lines up to the next level-1 or level-2 heading.
     *
     * @return list<array{heading: string, line: int, lines: array<int, string>}>
     */
    private static function sections(string $markdown, string $prefix): array
    {
        $sections = [];
        $current = null;

        foreach (self::proseLines($markdown) as $number => $line) {
            if (1 === preg_match('/^(#{1,2})\s+(.+?)\s*#*$/', $line, $heading)) {
                if (null !== $current) {
                    $sections[] = $current;
                }

                $current = '##' === $heading[1] && str_starts_with($heading[2], $prefix) ? ['heading' => $heading[2], 'line' => $number, 'lines' => []] : null;

                continue;
            }

            if (null !== $current) {
                $current['lines'][$number] = $line;
            }
        }

        if (null !== $current) {
            $sections[] = $current;
        }

        return $sections;
    }

    /**
     * @param array<int, string> $lines keyed by line number
     *
     * @return list<non-empty-array<int, string>> each run of consecutive lines that start with |
     */
    private static function tableBlocks(array $lines): array
    {
        $blocks = [];
        $block = [];
        $previous = 0;

        foreach ($lines as $number => $line) {
            if ([] !== $block && (!str_starts_with($line, '|') || $number !== $previous + 1)) {
                $blocks[] = $block;
                $block = [];
            }

            if (str_starts_with($line, '|')) {
                $block[$number] = $line;
            }

            $previous = $number;
        }

        if ([] !== $block) {
            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * @param non-empty-array<int, string> $block the table's lines keyed by line number
     * @param list<string> $expected
     *
     * @return array{table: Table|null, violations: list<string>}
     */
    private static function parseTable(array $block, string $section, array $expected): array
    {
        $numbers = array_keys($block);
        $lines = array_values($block);
        $header = self::cells($lines[0]);
        $headerLine = $numbers[0];
        $violations = [];

        if ($header !== $expected) {
            $violations[] = "{$headerLine}: the table header is \"| " . implode(' | ', $header) . ' |", not "| ' . implode(' | ', $expected) . ' |"';
        }

        if (!isset($lines[1]) || [] !== array_filter(self::cells($lines[1]), static fn (string $cell): bool => 1 !== preg_match('/^:?-+:?$/', $cell))) {
            $violations[] = "{$headerLine}: the table has no --- separator row under its header";
        }

        $columns = array_flip($header);

        if (!isset($columns['Status'], $columns['Blocked by'], $columns['Detail'])) {
            $violations[] = "{$headerLine}: the table has no Status, Blocked by and Detail columns, so its rows were not read";

            return ['table' => null, 'violations' => $violations];
        }

        $rows = [];
        $unaudited = false;

        foreach (array_slice($lines, 2) as $index => $line) {
            $number = $numbers[$index + 2];
            $cells = self::cells($line);

            if (count($cells) !== count($header)) {
                $violations[] = "{$number}: the row has " . count($cells) . ' cells and the header ' . count($header);

                continue;
            }

            if (in_array($cells[0], self::UNAUDITED, true)) {
                $unaudited = true;

                if ([] !== array_filter(array_slice($cells, 1), static fn (string $cell): bool => '—' !== $cell)) {
                    $violations[] = "{$number}: an unaudited row has — in every other cell";
                }

                continue;
            }

            $status = Status::tryFrom($cells[$columns['Status']]);

            if (null === $status) {
                $violations[] = "{$number}: \"{$cells[$columns['Status']]}\" is not a status in the vocabulary";

                continue;
            }

            $rows[] = new Row(
                feature: $cells[0],
                ports: isset($columns['Ports']) ? $cells[$columns['Ports']] : '',
                status: $status,
                blockedBy: $cells[$columns['Blocked by']],
                detail: $cells[$columns['Detail']],
                line: $number,
            );
        }

        return ['table' => new Table($section, $rows, $unaudited, $headerLine), 'violations' => $violations];
    }

    /**
     * @return list<string>
     */
    private static function cells(string $line): array
    {
        $line = (string) preg_replace(['/^\|/', '/(?<!\\\\)\|$/'], '', mb_trim($line));

        return array_map(mb_trim(...), preg_split('/(?<!\\\\)\|/', $line) ?: []);
    }

    /**
     * The lines outside the frontmatter and fenced blocks, keyed by 1-based line number.
     *
     * @return array<int, string>
     */
    private static function proseLines(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $prose = [];
        $fence = null;
        $frontmatter = '---' === $lines[0];

        foreach ($lines as $index => $line) {
            if ($frontmatter) {
                $frontmatter = 0 === $index || '---' !== $line;

                continue;
            }

            if (1 === preg_match('/^\s{0,3}(`{3,}|~{3,})/', $line, $marker)) {
                if (null === $fence) {
                    $fence = $marker[1];

                    continue;
                }

                if ($marker[1][0] === $fence[0] && mb_strlen($marker[1]) >= mb_strlen($fence)) {
                    $fence = null;

                    continue;
                }
            }

            if (null === $fence) {
                $prose[$index + 1] = $line;
            }
        }

        return $prose;
    }

    /**
     * @return list<array{level: int, text: string, line: int}>
     */
    private static function headings(string $markdown): array
    {
        $headings = [];

        foreach (self::proseLines($markdown) as $number => $line) {
            if (1 === preg_match('/^(#{1,6})\s+(.+?)\s*#*$/', $line, $match)) {
                $headings[] = ['level' => mb_strlen($match[1]), 'text' => $match[2], 'line' => $number];
            }
        }

        return $headings;
    }

    /**
     * A title as code comments and prose quote it: wrapped lines joined, comment leaders and backticks dropped.
     */
    private static function normalizeTitle(string $title): string
    {
        $title = (string) preg_replace('#\s*\n\s*(?:\*(?!\*)|//|\#)?\s*#', ' ', $title);

        return mb_trim((string) preg_replace('/\s+/', ' ', str_replace('`', '', $title)));
    }

    private static function isYamlComment(string $line): bool
    {
        if (1 === preg_match('/^\s*#/', $line)) {
            return true;
        }

        $value = (string) preg_replace('/^\s*(?:-\s+|[^\s:#]+:\s*)/', '', $line, 1);

        if (1 === preg_match('/^"(?:[^"\\\\]|\\\\.)*"(.*)$/', $value, $quoted) || 1 === preg_match("/^'(?:[^']|'')*'(.*)$/", $value, $quoted)) {
            $value = $quoted[1];
        }

        return 1 === preg_match('/(?:^|\s)#/', $value);
    }

    /**
     * @phpstan-assert-if-true list<string> $value
     */
    private static function isStringList(mixed $value): bool
    {
        return is_array($value) && array_is_list($value) && [] === array_filter($value, static fn (mixed $item): bool => !is_string($item));
    }

    private static function replaceIndex(string $content, string $path, string $table): string
    {
        $start = mb_strpos($content, self::INDEX_START);
        $end = mb_strpos($content, self::INDEX_END);

        if (false === $start || false === $end || $end < $start || 1 !== mb_substr_count($content, self::INDEX_START) || 1 !== mb_substr_count($content, self::INDEX_END)) {
            throw new UnexpectedValueException("{$path} needs exactly one " . self::INDEX_START . ' … ' . self::INDEX_END . ' pair; the generator never adds them');
        }

        return mb_substr($content, 0, $start) . self::INDEX_START . "\n" . $table . "\n" . mb_substr($content, $end);
    }

    /**
     * @return list<string>
     */
    private function tracked(): array
    {
        if (null === $this->tracked) {
            exec('git -C ' . escapeshellarg($this->root) . ' ls-files', $output, $status);

            if (0 !== $status) {
                throw new RuntimeException('git ls-files failed; the trail reads tracked files through git.');
            }

            $this->tracked = $output;
        }

        return $this->tracked;
    }

    /**
     * @return list<string>
     */
    private function ownableFiles(): array
    {
        return array_values(array_filter($this->tracked(), static fn (string $path): bool => 1 === preg_match(self::OWNABLE, $path)));
    }

    /**
     * @return list<string>
     */
    private function packages(): array
    {
        $packages = array_map(basename(...), glob($this->root . '/packages/*', GLOB_ONLYDIR) ?: []);
        sort($packages);

        return $packages;
    }

    /**
     * @return list<string> repo-relative, sorted by file name
     */
    private function detailPaths(string $package): array
    {
        $paths = [];

        foreach (glob("{$this->root}/docs/progress/{$package}/*.md") ?: [] as $file) {
            if ('README.md' !== basename($file)) {
                $paths[] = "docs/progress/{$package}/" . basename($file);
            }
        }

        sort($paths);

        return $paths;
    }

    /**
     * @return list<string> every markdown file of the trail, repo-relative
     */
    private function trailPaths(): array
    {
        $paths = array_map(
            fn (string $file): string => mb_substr($file, mb_strlen($this->root) + 1),
            [...glob($this->root . '/docs/progress/*.md') ?: [], ...glob($this->root . '/docs/progress/*/*.md') ?: []],
        );
        sort($paths);

        return $paths;
    }

    private function readme(string $package): string
    {
        return "docs/progress/{$package}/README.md";
    }

    private function read(string $path): string
    {
        $content = is_file($this->root . '/' . $path) ? file_get_contents($this->root . '/' . $path) : false;

        if (false === $content) {
            throw new UnexpectedValueException("{$path} cannot be read");
        }

        return $content;
    }

    /**
     * @return list<DetailFile>
     */
    private function packageFiles(string $package): array
    {
        $files = [];

        foreach ($this->detailPaths($package) as $path) {
            ['file' => $file, 'violations' => $violations] = $this->inspect($path, $package);

            if (null === $file) {
                $more = 1 < count($violations) ? sprintf(' (and %d more, which the format check lists)', count($violations) - 1) : '';

                throw new UnexpectedValueException(implode('', array_slice($violations, 0, 1)) . $more);
            }

            $files[] = $file;
        }

        return $files;
    }

    /**
     * @return list<Table>
     */
    private function unportedTables(string $package): array
    {
        $readme = $this->readme($package);

        try {
            return self::tables($this->read($readme), 'Unported');
        } catch (UnexpectedValueException $unexpectedValueException) {
            throw new UnexpectedValueException("{$readme}: " . str_replace("\n", "\n{$readme}: ", $unexpectedValueException->getMessage()), $unexpectedValueException->getCode(), previous: $unexpectedValueException);
        }
    }

    /**
     * Every rule check 1 and 3 hold a detail file to. The file is null when its frontmatter types or its tables
     * are too broken to build one; row-rule and ports violations still build it.
     *
     * @return array{file: DetailFile|null, violations: list<string>}
     */
    private function inspect(string $path, string $package): array
    {
        $markdown = $this->read($path);
        $violations = [];
        $frontmatter = [];

        try {
            $frontmatter = self::frontmatter($markdown);

            foreach ($this->frontmatterViolations($frontmatter) as $violation) {
                $violations[] = "{$path}: {$violation}";
            }
        } catch (UnexpectedValueException $unexpectedValueException) {
            $violations[] = "{$path}: {$unexpectedValueException->getMessage()}";
        }

        ['tables' => $tables, 'violations' => $tableViolations] = self::scanTables($markdown, 'Surface');

        foreach ($tableViolations as $violation) {
            $violations[] = "{$path}:{$violation}";
        }

        $hasRow = false;

        foreach ($tables as $table) {
            $hasRow = $hasRow || $table->unaudited || [] !== $table->rows;

            foreach ($table->rows as $row) {
                foreach (self::rowViolations($row) as $violation) {
                    $violations[] = "{$path}:{$violation}";
                }
            }
        }

        if (!$hasRow) {
            $violations[] = "{$path}: has no Surface row";
        }

        $description = $frontmatter['description'] ?? null;
        $ports = $frontmatter['ports'] ?? null;
        $owns = $frontmatter['owns'] ?? null;

        if (!is_string($description) || !self::isStringList($ports) || !self::isStringList($owns) || [] !== $tableViolations) {
            return ['file' => null, 'violations' => $violations];
        }

        $sections = array_values(array_map(
            static fn (array $heading): string => $heading['text'],
            array_filter(self::headings($markdown), static fn (array $heading): bool => 2 === $heading['level']),
        ));

        return [
            'file' => new DetailFile($path, $package, $description, $ports, $owns, $tables, $sections),
            'violations' => $violations,
        ];
    }

    /**
     * Check 3 for a package README: exactly one Unported table, following the row rules, each Filament cell a
     * link to an existing vendor/filament/ path, and a skipped or blocked row linking its reason.
     *
     * @return list<string>
     */
    private function readmeViolations(string $package): array
    {
        $readme = $this->readme($package);
        $markdown = $this->read($readme);
        $sections = count(self::sections($markdown, 'Unported'));

        if (1 !== $sections) {
            return ["{$readme}: holds {$sections} ## Unported sections; it holds exactly one"];
        }

        ['tables' => $tables, 'violations' => $found] = self::scanTables($markdown, 'Unported');
        $violations = array_map(static fn (string $violation): string => "{$readme}:{$violation}", $found);
        $vendor = realpath($this->root . '/vendor/filament');

        foreach ($tables as $table) {
            foreach ($table->rows as $row) {
                foreach (self::rowViolations($row) as $violation) {
                    $violations[] = "{$readme}:{$violation}";
                }

                $target = 1 === preg_match('/^\[[^\]]+\]\(([^)\s#]+)(?:#[^)\s]*)?\)$/', $row->feature, $link)
                    ? realpath("{$this->root}/docs/progress/{$package}/" . rawurldecode($link[1]))
                    : false;

                if (false === $target || false === $vendor || !str_starts_with($target . '/', $vendor . '/')) {
                    $violations[] = "{$readme}:{$row->line}: the Filament cell is one link to an existing vendor/filament/ path, not \"{$row->feature}\"";
                }

                if (in_array($row->status, [Status::Skipped, Status::Blocked], true) && '—' === $row->detail) {
                    $violations[] = "{$readme}:{$row->line}: a {$row->status->value} Unported row links its reason from Detail";
                }
            }
        }

        return $violations;
    }

    /**
     * @return array<string, Closure(): string> README path → its index rendering
     */
    private function indexes(): array
    {
        $indexes = ['docs/progress/README.md' => $this->renderRootIndex(...)];

        foreach ($this->packages() as $package) {
            $indexes[$this->readme($package)] = fn (): string => $this->renderPackageIndex($package);
        }

        return $indexes;
    }
}

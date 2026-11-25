<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Source;

use LogicException;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

use function dirname;

/**
 * The names Sylament declares that Filament marks `@deprecated` on their counterpart: a class-like whose counterpart
 * is deprecated is listed as `Sylament\FQCN`, and a method, property or constant as `Sylament\FQCN::member`
 * (`::$name` for a property), under the class-like that declares it.
 *
 * A Sylament class-like's counterpart is the Filament class-like the Renames table of `docs/progress/renames.md`
 * pairs it with; else the one at its translated name (`Sylament\Panels\` and `Sylament\<Pkg>\` read as `Filament\`
 * and `Filament\<Pkg>\`, and the Livewire idiom of `docs/progress/translations.md` read back); else the Filament
 * class-like of its short name, each one sharing the longest leading run of namespace segments when several do.
 * A member is resolved as PHP resolves it: on the Filament side through parents, interfaces and traits, an own
 * declaration over a trait's over a parent's, so a member Filament deprecates on a parent and redeclares undeprecated
 * on the counterpart is not listed; on the Sylament side through the class-like's own declarations and its traits,
 * never its parent, whose members are judged against the parent's own counterpart.
 *
 * Known gap: a Sylament rename the Renames table records only as a method or parameter row (no class pair) escapes
 * the member match, since its name differs from Filament's.
 *
 * @phpstan-type Index array<string, array{deprecated: bool, parents: list<string>, traits: list<string>, members: array<string, array{name: string, deprecated: bool}>}>
 */
final class FilamentDeprecations
{
    /**
     * The Livewire idiom of `docs/progress/translations.md`, as Sylament's short-name fragment to Filament's.
     */
    private const array IDIOMS = ['LiveComponent' => 'Livewire'];

    private const string RENAMES = 'docs/progress/renames.md';

    /**
     * @param array<string, string> $renamed the renamed counterparts, as `renamedCounterparts()` reads them
     *
     * @return list<string> the deprecated names the Sylament source declares, matched against the Filament source,
     *                      sorted
     */
    public static function matches(string $filamentSource, string $sylamentSource, array $renamed = []): array
    {
        return self::compare(self::index([$filamentSource]), self::index([$sylamentSource]), $renamed);
    }

    /**
     * @return list<string> the deprecated names every package's `src/` declares, matched against
     *                      `vendor/filament/<pkg>/src/`, sorted
     */
    public static function shipped(): array
    {
        $root = dirname(__DIR__, 3);

        return self::compare(
            self::index(self::sources($root . '/vendor/filament/*/src')),
            self::index(self::sources($root . '/packages/*/src')),
            self::renamedCounterparts(file_get_contents($root . '/' . self::RENAMES) ?: throw new LogicException(sprintf('%s could not be read.', self::RENAMES))),
        );
    }

    /**
     * @return array<string, string> each class, trait or interface row of the Renames table, as its Sylament
     *                               home's name => Filament's name
     */
    public static function renamedCounterparts(string $renames): array
    {
        $counterparts = [];

        $pattern = '/^\| `(Filament(?:\\\\\w+)+)` \| [^|]+ \| (?:class|trait|interface) \| `(Sylament(?:\\\\\w+)+)` \|/m';

        if (preg_match_all($pattern, $renames, $rows, PREG_SET_ORDER) === false) {
            throw new LogicException('The Renames table could not be read.');
        }

        foreach ($rows as $row) {
            $counterparts[$row[2]] = $row[1];
        }

        return $counterparts;
    }

    /**
     * @return list<string>
     */
    private static function sources(string $directories): array
    {
        $directories = glob($directories, GLOB_ONLYDIR) ?: [];

        if ($directories === []) {
            throw new LogicException('No source directory matches.');
        }

        $sources = [];

        foreach (Finder::create()->files()->in($directories)->name('*.php')->sortByName() as $file) {
            $sources[] = $file->getContents();
        }

        return $sources;
    }

    /**
     * @param list<string> $sources
     *
     * @return Index each class-like by its name; a member keyed by its kind and its name, a method's lower-cased
     */
    private static function index(array $sources): array
    {
        $parser = new ParserFactory()->createForHostVersion();
        $index = [];

        foreach ($sources as $source) {
            foreach (self::classLikes($parser, $source) as $classLike) {
                $name = $classLike->namespacedName?->toString();

                if ($name === null) {
                    continue;
                }

                $parents = match (true) {
                    $classLike instanceof Class_ => [...($classLike->extends instanceof Name ? [$classLike->extends] : []), ...$classLike->implements],
                    $classLike instanceof Interface_ => $classLike->extends,
                    $classLike instanceof Enum_ => $classLike->implements,
                    default => [],
                };

                $traits = [];

                foreach ($classLike->getTraitUses() as $use) {
                    foreach ($use->traits as $trait) {
                        $traits[] = $trait->toString();
                    }
                }

                $members = [];

                foreach ($classLike->getMethods() as $method) {
                    $members['method:' . $method->name->toLowerString()] = ['name' => $method->name->toString(), 'deprecated' => self::isDeprecated($method->getDocComment()?->getText())];
                }

                foreach ($classLike->getProperties() as $property) {
                    foreach ($property->props as $item) {
                        $members['property:' . $item->name->toString()] = ['name' => '$' . $item->name->toString(), 'deprecated' => self::isDeprecated($property->getDocComment()?->getText())];
                    }
                }

                foreach ($classLike->getConstants() as $constant) {
                    foreach ($constant->consts as $item) {
                        $members['constant:' . $item->name->toString()] = ['name' => $item->name->toString(), 'deprecated' => self::isDeprecated($constant->getDocComment()?->getText())];
                    }
                }

                $index[$name] = [
                    'deprecated' => self::isDeprecated($classLike->getDocComment()?->getText()),
                    'parents' => array_values(array_map(static fn (Name $parent): string => $parent->toString(), $parents)),
                    'traits' => $traits,
                    'members' => $members,
                ];
            }
        }

        return $index;
    }

    /**
     * @return list<ClassLike>
     */
    private static function classLikes(Parser $parser, string $source): array
    {
        $statements = $parser->parse($source) ?? [];
        new NodeTraverser(new NameResolver())->traverse($statements);

        return array_values(new NodeFinder()->findInstanceOf($statements, ClassLike::class));
    }

    private static function isDeprecated(?string $docComment): bool
    {
        return $docComment !== null && preg_match('/^\s*(?:\/\*\*|\*)?\s*@deprecated\b/m', $docComment) === 1;
    }

    /**
     * @param Index $filament
     * @param Index $sylament
     * @param array<string, string> $renamed
     *
     * @return list<string>
     */
    private static function compare(array $filament, array $sylament, array $renamed): array
    {
        $byShortName = [];

        foreach (array_keys($filament) as $name) {
            $byShortName[self::shortName($name)][] = $name;
        }

        $matches = [];

        foreach (array_keys($sylament) as $name) {
            foreach (self::counterparts($name, $filament, $byShortName, $renamed) as $counterpart) {
                if ($filament[$counterpart]['deprecated']) {
                    $matches[$name] = true;
                }

                $filamentMembers = self::resolve($filament, $counterpart, withParents: true);

                foreach (self::resolve($sylament, $name, withParents: false) as $key => $member) {
                    if (($filamentMembers[$key]['deprecated'] ?? false) === true) {
                        $matches[$member['declaredIn'] . '::' . $member['name']] = true;
                    }
                }
            }
        }

        $matches = array_keys($matches);
        sort($matches, SORT_STRING);

        return $matches;
    }

    /**
     * @param Index $filament
     * @param array<string, list<string>>                                                                                                                   $byShortName
     * @param array<string, string> $renamed
     *
     * @return list<string> the Filament class-likes the Sylament one is matched against: one, or every one of its
     *                      short name that ties for the closest namespace
     */
    private static function counterparts(string $name, array $filament, array $byShortName, array $renamed): array
    {
        if (isset($renamed[$name])) {
            return isset($filament[$renamed[$name]]) ? [$renamed[$name]] : [];
        }

        $segments = explode('\\', $name);
        array_shift($segments);

        if (($segments[0] ?? null) === 'Panels') {
            array_shift($segments);
        }

        $shortName = strtr((string) array_pop($segments), self::IDIOMS);
        $translated = implode('\\', ['Filament', ...$segments, $shortName]);

        if (isset($filament[$translated])) {
            return [$translated];
        }

        $best = [];
        $bestScore = -1;

        foreach ($byShortName[$shortName] ?? [] as $candidate) {
            $candidateSegments = explode('\\', $candidate);
            array_shift($candidateSegments);
            array_pop($candidateSegments);

            $score = 0;

            while (isset($segments[$score], $candidateSegments[$score]) && $segments[$score] === $candidateSegments[$score]) {
                $score++;
            }

            if ($score > $bestScore) {
                $best = [$candidate];
                $bestScore = $score;
            } elseif ($score === $bestScore) {
                $best[] = $candidate;
            }
        }

        return $best;
    }

    /**
     * @param Index $index
     * @param array<string, true> $visiting
     *
     * @return array<string, array{name: string, deprecated: bool, declaredIn: string}> the members the class-like ends up with, keyed as in the index
     */
    private static function resolve(array $index, string $name, bool $withParents, array $visiting = []): array
    {
        if (!isset($index[$name]) || isset($visiting[$name])) {
            return [];
        }

        $visiting[$name] = true;
        $members = [];

        if ($withParents) {
            foreach ($index[$name]['parents'] as $parent) {
                $members = [...$members, ...self::resolve($index, $parent, $withParents, $visiting)];
            }
        }

        foreach ($index[$name]['traits'] as $trait) {
            $members = [...$members, ...self::resolve($index, $trait, $withParents, $visiting)];
        }

        foreach ($index[$name]['members'] as $key => $member) {
            $members[$key] = [...$member, 'declaredIn' => $name];
        }

        return $members;
    }

    private static function shortName(string $name): string
    {
        $position = mb_strrpos($name, '\\');

        return $position === false ? $name : mb_substr($name, $position + 1);
    }
}

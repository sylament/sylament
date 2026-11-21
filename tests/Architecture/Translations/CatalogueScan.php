<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Translations;

use LogicException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Validator\Validation;

/**
 * Compares the keys a package's code uses with the catalogues that hold them: a key the code names and the catalogue
 * lacks renders raw, a key nothing names is dead weight, and a domain the package may not name renders raw once the
 * split publishes the package alone.
 */
final class CatalogueScan
{
    private const string VALIDATORS = 'validators';

    /**
     * @var array<string, MessageCatalogue> the extraction of each package directory, since the unused arm reads every
     *                                      dependent of every package
     */
    private static array $extractions = [];

    /**
     * @return list<string> one sorted line per problem: `missing <domain>: <key>`, `unused <domain>: <key>`,
     *                      `forbidden <domain>: <key>`, or `misplaced <domain>: <catalogue file>`
     */
    public static function problems(ExtractorInterface $extractor, string $package, string $packagesDirectory = Packages::DIRECTORY): array
    {
        $own = Packages::domain($package);
        $allowed = [$own, self::VALIDATORS, ...array_map(Packages::domain(...), Packages::requires($package, $packagesDirectory))];
        $used = self::extraction($extractor, $packagesDirectory, $package);
        $catalogues = self::catalogues($packagesDirectory, $package);
        $problems = [];

        foreach (array_filter($used->getDomains(), is_string(...)) as $domain) {
            if ($domain === self::VALIDATORS) {
                continue;
            }

            $keys = array_map(strval(...), array_keys($used->all($domain)));

            if (!in_array($domain, $allowed, true)) {
                array_push($problems, ...array_map(static fn (string $key): string => sprintf('forbidden %s: %s', $domain, $key), $keys));

                continue;
            }

            $held = self::catalogues($packagesDirectory, self::owner($domain, $packagesDirectory))[$domain] ?? [];

            foreach (array_diff($keys, $held) as $key) {
                $problems[] = sprintf('missing %s: %s', $domain, $key);
            }
        }

        $ownUsed = [];

        foreach ([$package, ...Packages::dependents($package, $packagesDirectory)] as $user) {
            array_push($ownUsed, ...array_map(strval(...), array_keys(self::extraction($extractor, $packagesDirectory, $user)->all($own))));
        }

        foreach (array_diff($catalogues[$own] ?? [], $ownUsed) as $key) {
            $problems[] = sprintf('unused %s: %s', $own, $key);
        }

        $validatorIds = [...array_map(strval(...), array_keys($used->all(self::VALIDATORS))), ...self::validatorMessages($packagesDirectory . '/' . $package . '/src')];
        $heldIds = $catalogues[self::VALIDATORS] ?? [];

        foreach (array_unique(array_diff($validatorIds, $heldIds, self::symfonyValidatorIds())) as $id) {
            $problems[] = sprintf('missing %s: %s', self::VALIDATORS, $id);
        }

        foreach (array_diff($heldIds, $validatorIds) as $id) {
            $problems[] = sprintf('unused %s: %s', self::VALIDATORS, $id);
        }

        foreach (self::catalogueFiles($packagesDirectory, $package) as $file => $domain) {
            if ($domain !== $own && $domain !== self::VALIDATORS) {
                $problems[] = sprintf('misplaced %s: translations/%s', $domain, $file);
            }
        }

        sort($problems, SORT_STRING);

        return $problems;
    }

    /**
     * Every validator message the package ships as a literal: in its own `Validation/` constraints, a `…message`
     * property or promoted-parameter default, a `…message: $message ?? '…'` argument to `parent::__construct()` and a
     * `$this->…message = $message ?? …` assignment; and anywhere in its `src/`, a `buildViolation()` id and a value
     * written into a `$refusals[…]` map, which `RecordKeyGuard::constraint()` turns into violations, each a literal
     * or a `self::`/`static::` string constant of the file. The extractor sees none of these shapes.
     *
     * @return list<string>
     */
    public static function validatorMessages(string $sourceDirectory): array
    {
        if (!is_dir($sourceDirectory)) {
            return [];
        }

        $parser = new ParserFactory()->createForHostVersion();
        $finder = new NodeFinder();
        $messages = [];

        foreach (Finder::create()->files()->in($sourceDirectory)->name('*.php')->sortByName() as $file) {
            $nodes = $parser->parse($file->getContents()) ?? [];
            $constants = self::stringConstants($finder->findInstanceOf($nodes, ClassConst::class));

            foreach ($finder->findInstanceOf($nodes, MethodCall::class) as $call) {
                $id = $call->name instanceof Identifier && $call->name->toString() === 'buildViolation' && !$call->isFirstClassCallable() ? ($call->getArgs()[0] ?? null)?->value : null;

                if ($id instanceof Expr) {
                    $messages = [...$messages, ...self::violationId($id, $constants)];
                }
            }

            foreach ($finder->findInstanceOf($nodes, Assign::class) as $assign) {
                if ($assign->var instanceof ArrayDimFetch && $assign->var->var instanceof Variable && $assign->var->var->name === 'refusals') {
                    $messages = [...$messages, ...self::violationId($assign->expr, $constants)];
                }
            }

            if (!str_starts_with($file->getRelativePath() . '/', 'Validation/')) {
                continue;
            }

            foreach ($finder->findInstanceOf($nodes, Property::class) as $property) {
                foreach ($property->props as $item) {
                    $messages = [...$messages, ...self::messageDefault($item)];
                }
            }

            foreach ($finder->findInstanceOf($nodes, Param::class) as $parameter) {
                if ($parameter->flags !== 0) {
                    $messages = [...$messages, ...self::messageDefault($parameter)];
                }
            }

            foreach ($finder->findInstanceOf($nodes, StaticCall::class) as $call) {
                if ($call->isFirstClassCallable() || !$call->class instanceof Name || $call->class->toLowerString() !== 'parent' || !$call->name instanceof Identifier || $call->name->toLowerString() !== '__construct') {
                    continue;
                }

                foreach ($call->getArgs() as $argument) {
                    if ($argument->name === null || preg_match('/message$/i', $argument->name->toString()) !== 1) {
                        continue;
                    }

                    $messages = [...$messages, ...self::literalValues($argument->value)];
                }
            }

            foreach ($finder->findInstanceOf($nodes, Assign::class) as $assign) {
                if ($assign->var instanceof PropertyFetch && $assign->var->name instanceof Identifier && preg_match('/message$/i', $assign->var->name->toString()) === 1) {
                    $messages = [...$messages, ...self::literalValues($assign->expr)];
                }
            }
        }

        return $messages;
    }

    /**
     * @param array<ClassConst> $declarations
     *
     * @return array<string, string> each constant a file declares with a string literal, by name
     */
    private static function stringConstants(array $declarations): array
    {
        $constants = [];

        foreach ($declarations as $declaration) {
            foreach ($declaration->consts as $constant) {
                if ($constant->value instanceof String_) {
                    $constants[$constant->name->toString()] = $constant->value->value;
                }
            }
        }

        return $constants;
    }

    /**
     * @param array<string, string> $constants
     *
     * @return list<string> the id an expression names: a literal, or a `self::` or `static::` constant of the file
     */
    private static function violationId(Expr $expression, array $constants): array
    {
        if ($expression instanceof String_) {
            return [$expression->value];
        }

        if ($expression instanceof ClassConstFetch && $expression->class instanceof Name && in_array($expression->class->toLowerString(), ['self', 'static'], true) && $expression->name instanceof Identifier) {
            return isset($constants[$expression->name->toString()]) ? [$constants[$expression->name->toString()]] : [];
        }

        return [];
    }

    /**
     * @return list<string> the literals an expression can evaluate to: itself, a `??` fallback, or a ternary or
     *                      match branch
     */
    private static function literalValues(Expr $expression): array
    {
        return match (true) {
            $expression instanceof String_ => [$expression->value],
            $expression instanceof Coalesce => self::literalValues($expression->right),
            $expression instanceof Ternary => [...$expression->if instanceof Expr ? self::literalValues($expression->if) : [], ...self::literalValues($expression->else)],
            $expression instanceof Match_ => array_merge([], ...array_map(static fn (MatchArm $arm): array => self::literalValues($arm->body), $expression->arms)),
            default => [],
        };
    }

    /**
     * @return list<string> the ids symfony/validator's own English catalogue translates, which a constraint reusing
     *                      one of its texts needs no entry of its own for
     */
    private static function symfonyValidatorIds(): array
    {
        $path = dirname((string) new ReflectionClass(Validation::class)->getFileName()) . '/Resources/translations/validators.en.xlf';

        return array_map(strval(...), array_keys(new XliffFileLoader()->load($path, 'en', self::VALIDATORS)->all(self::VALIDATORS)));
    }

    /**
     * @return list<string> the default, when a `…message` property or parameter has a literal one
     */
    private static function messageDefault(PropertyItem|Param $node): array
    {
        $name = $node instanceof Param ? ($node->var instanceof Variable && is_string($node->var->name) ? $node->var->name : '') : $node->name->toString();

        return preg_match('/message$/i', $name) === 1 && $node->default instanceof Expr ? self::literalValues($node->default) : [];
    }

    private static function extraction(ExtractorInterface $extractor, string $packagesDirectory, string $package): MessageCatalogue
    {
        $directory = $packagesDirectory . '/' . $package;

        if (!isset(self::$extractions[$directory])) {
            $catalogue = new MessageCatalogue('en');

            foreach (['src', 'templates'] as $half) {
                if (is_dir($directory . '/' . $half)) {
                    $extractor->extract($directory . '/' . $half, $catalogue);
                }
            }

            self::$extractions[$directory] = $catalogue;
        }

        return self::$extractions[$directory];
    }

    /**
     * @return array<string, list<string>> the keys each of the package's catalogue files holds, by domain
     */
    private static function catalogues(string $packagesDirectory, string $package): array
    {
        $loader = new YamlFileLoader();
        $keys = [];

        foreach (self::catalogueFiles($packagesDirectory, $package) as $file => $domain) {
            $catalogue = $loader->load($packagesDirectory . '/' . $package . '/translations/' . $file, 'en', $domain);
            $keys[$domain] = [...$keys[$domain] ?? [], ...array_map(strval(...), array_keys($catalogue->all($domain)))];
        }

        return $keys;
    }

    /**
     * @return array<string, string> each `translations/*.en.yaml` file name, mapped to the domain it holds
     */
    private static function catalogueFiles(string $packagesDirectory, string $package): array
    {
        $files = [];

        foreach (glob($packagesDirectory . '/' . $package . '/translations/*') ?: [] as $path) {
            if (preg_match('/^(?:validators\.en\.yaml|(?!validators\+)(?<domain>[a-z0-9-]+)\+intl-icu\.en\.yaml)$/', basename($path), $match) !== 1) {
                throw new LogicException(sprintf('%s is not named <domain>+intl-icu.en.yaml or validators.en.yaml (.claude/rules/catalogues.md).', $path));
            }

            $files[basename($path)] = $match['domain'] ?? self::VALIDATORS;
        }

        return $files;
    }

    /**
     * The package whose catalogue holds a domain the caller may name.
     */
    private static function owner(string $domain, string $packagesDirectory): string
    {
        foreach (Packages::names($packagesDirectory) as $package) {
            if (Packages::domain($package) === $domain) {
                return $package;
            }
        }

        throw new LogicException(sprintf('No package under %s holds the domain %s.', $packagesDirectory, $domain));
    }
}

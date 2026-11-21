<?php

declare(strict_types=1);

namespace Sylament\Tests\Architecture\Translations;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotEqual;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Identifier;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Environment;
use Twig\Source;
use Twig\Token;

/**
 * Finds the user-facing text a package ships as a literal: text and text attributes in its templates, string
 * literals in its template expressions and `<twig:>` text props, literals handed to a text setter or returned by a
 * text getter in its PHP, and string defaults and text assignments in its TypeScript. Each line is `<path>: <text>`,
 * the path relative to the package and without a line number, so a failure names what to move rather than where it sat.
 */
final class LiteralScan
{
    /**
     * The text setters of the inventory's accepting sites (§1 of each package file), less the ones whose first
     * argument is a name, an identifier, a format or an object rather than text.
     */
    public const array TEXT_SETTERS = [
        'aboveContent', 'aboveErrorMessage', 'aboveLabel', 'addActionLabel', 'addBetweenActionLabel', 'afterContent',
        'afterHeader', 'afterLabel', 'alt', 'badge', 'badgeTooltip', 'beforeContent', 'beforeLabel', 'belowContent',
        'belowErrorMessage', 'belowLabel', 'body', 'brandName', 'breadcrumb', 'content', 'copyMessage',
        'createOptionModalHeading', 'deleteActionLabel', 'description', 'descriptions', 'editOptionModalHeading',
        'emptyStateDescription', 'emptyStateHeading', 'emptyTooltip', 'failureNotificationTitle', 'falseLabel',
        'footer', 'headerTooltip', 'heading', 'helperText', 'hint', 'hintIconTooltip', 'indicator', 'itemLabel',
        'keyLabel', 'keyPlaceholder', 'label', 'labelBetweenItems', 'loadingMessage', 'maxItemsMessage',
        'modalCancelActionLabel', 'modalDescription', 'modalHeading', 'modalSubmitActionLabel', 'modelLabel',
        'navigationGroup', 'navigationLabel', 'noOptionsMessage', 'noSearchResultsMessage', 'options', 'placeholder',
        'pluralModelLabel', 'pluralRecordLabel', 'postfix', 'prefix', 'recordLabel', 'recordTitle',
        'reorderActionLabel', 'searchingMessage', 'searchPlaceholder', 'searchPrompt', 'subheading',
        'successNotificationTitle', 'suffix', 'tagPrefix', 'tagSuffix', 'title', 'tooltip', 'tooltips', 'trueLabel',
        'validationMessages', 'valueLabel', 'valuePlaceholder',
    ];

    /**
     * The attributes a browser reads out or shows as text.
     */
    public const array TEXT_ATTRIBUTES = ['aria-label', 'aria-description', 'title', 'placeholder', 'alt'];

    /**
     * The props a `<twig:>` component renders as text.
     */
    public const array TEXT_PROPS = ['label', 'heading', 'title', 'description', 'placeholder', 'tooltip'];

    /**
     * The functions whose result is text built from their string arguments.
     */
    private const array TEXT_BUILDERS = [
        'sprintf' => 0, 'vsprintf' => 0, 'str_replace' => 2, 'ucfirst' => 0, 'lcfirst' => 0, 'ucwords' => 0,
        'strtolower' => 0, 'strtoupper' => 0, 'mb_strtolower' => 0, 'mb_strtoupper' => 0, 'trim' => 0, 'rtrim' => 0,
        'ltrim' => 0,
    ];

    /**
     * An array key naming text: a text prop or attribute, or a name ending in one of these words.
     */
    private const string TEXT_KEY = '/^(?:aria-label|aria-description|alt)$|(?:label|heading|title|description|placeholder|tooltip|message)$/i';

    /**
     * A getter returning text beyond a text setter's twin: a `get…` name ending in one of these words.
     */
    private const string TEXT_GETTER = '/^get\w*(?:title|label|heading|description|placeholder|tooltip|message)$/i';

    /**
     * A TypeScript binding naming text, by the word its name ends in.
     */
    private const string TEXT_BINDING = '(?:text|label|message|error|placeholder|prompt|title|heading|tooltip|description)';

    private const string BOUNDARY = "\x01";

    private const string EXPRESSION = "\x02";

    /**
     * @return list<string> every user-facing literal the package ships, as sorted `<path>: <text>` lines
     */
    public static function literals(Environment $twig, string $package, string $packagesDirectory = Packages::DIRECTORY): array
    {
        $directory = $packagesDirectory . '/' . $package;
        $lines = [];

        foreach (self::files($directory . '/templates', '*.twig') as $path => $code) {
            foreach (self::templateLiterals($twig, $code, $path) as $text) {
                $lines[] = 'templates/' . $path . ': ' . $text;
            }
        }

        foreach (self::files($directory . '/src', '*.php') as $path => $code) {
            foreach (self::phpLiterals($code) as $text) {
                $lines[] = 'src/' . $path . ': ' . $text;
            }
        }

        foreach (self::files($directory . '/assets/src', '*.ts') as $path => $code) {
            foreach (self::typeScriptLiterals($code) as $text) {
                $lines[] = 'assets/src/' . $path . ': ' . $text;
            }
        }

        sort($lines, SORT_STRING);

        return $lines;
    }

    /**
     * @return list<string>
     */
    public static function templateLiterals(Environment $twig, string $code, string $name): array
    {
        $stream = $twig->tokenize(new Source($code, $name));
        $tokens = [];

        while (!$stream->isEOF()) {
            $tokens[] = $stream->next();
        }

        $markup = '';
        $literals = [];

        foreach ($tokens as $index => $token) {
            if ($token->test(Token::TEXT_TYPE)) {
                $markup .= self::value($token);
            } elseif ($token->test(Token::BLOCK_START_TYPE)) {
                $markup .= self::BOUNDARY;
            } elseif ($token->test(Token::VAR_START_TYPE)) {
                $markup .= self::EXPRESSION;
            } elseif ($token->test(Token::STRING_TYPE)) {
                $literal = self::expressionLiteral($tokens, $index);

                if ($literal !== null) {
                    $literals[] = $literal;
                }
            }
        }

        $markup = preg_replace(['/<!--.*?-->/s', '/<(script|style)\b[^>]*>.*?<\/\1>/is'], self::BOUNDARY, $markup) ?? $markup;
        $markup = preg_replace('/<!doctype[^>]*>/i', '', $markup) ?? $markup;

        $tag = '/<\/?(?:[a-zA-Z][\w:.-]*|' . self::EXPRESSION . ')(?:[^<>"\']|"[^"]*"|\'[^\']*\')*>/';
        preg_match_all($tag, $markup, $tags);

        foreach ($tags[0] as $element) {
            preg_match_all('/\s([\w:.@-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/', $element, $attributes, PREG_SET_ORDER);

            foreach ($attributes as $attribute) {
                if (in_array(mb_strtolower($attribute[1]), self::TEXT_ATTRIBUTES, true)) {
                    $literals = [...$literals, ...self::markupText(($attribute[2] ?? '') . ($attribute[3] ?? ''))];
                }
            }
        }

        foreach (preg_split($tag, $markup) ?: [] as $between) {
            $literals = [...$literals, ...self::markupText($between)];
        }

        return $literals;
    }

    /**
     * @return list<string>
     */
    public static function phpLiterals(string $code): array
    {
        $nodes = new ParserFactory()->createForHostVersion()->parse($code) ?? [];
        $traverser = new NodeTraverser(new NameResolver());
        $nodes = $traverser->traverse($nodes);
        $finder = new NodeFinder();
        $excluded = self::nonTextStrings($finder, $nodes);
        $reported = [];
        $literals = [];
        $setters = array_map(strtolower(...), self::TEXT_SETTERS);
        $getters = [...array_map(static fn (string $setter): string => 'get' . $setter, $setters), ...array_map(static fn (string $setter): string => 'getdefault' . $setter, $setters)];

        $report = static function (Node $node) use (&$excluded, &$reported, &$literals): void {
            $id = spl_object_id($node);

            if (isset($excluded[$id]) || isset($reported[$id])) {
                return;
            }

            $reported[$id] = true;
            $text = self::phpText($node);

            if ($text !== null) {
                $literals[] = $text;
            }
        };

        foreach ($finder->find($nodes, static fn (Node $node): bool => $node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall) as $call) {
            if (!($call instanceof MethodCall || $call instanceof NullsafeMethodCall || $call instanceof StaticCall) || !$call->name instanceof Identifier || !in_array($call->name->toLowerString(), $setters, true)) {
                continue;
            }

            $argument = $call->isFirstClassCallable() ? null : $call->getArgs()[0] ?? null;

            if ($argument === null) {
                continue;
            }

            foreach (self::strings($finder, $argument->value) as $string) {
                $report($string);
            }
        }

        foreach ($finder->findInstanceOf($nodes, Property::class) as $property) {
            foreach ($property->props as $item) {
                if ($item->default !== null && in_array($item->name->toLowerString(), $setters, true)) {
                    foreach (self::returned($item->default) as $string) {
                        $report($string);
                    }
                }
            }
        }

        foreach ($finder->findInstanceOf($nodes, ArrayItem::class) as $item) {
            if ($item->key instanceof String_ && preg_match(self::TEXT_KEY, $item->key->value) === 1) {
                foreach (self::returned($item->value) as $string) {
                    $report($string);
                }
            }
        }

        foreach ($finder->findInstanceOf($nodes, ClassMethod::class) as $method) {
            if (!in_array($method->name->toLowerString(), $getters, true) && preg_match(self::TEXT_GETTER, $method->name->toString()) !== 1) {
                continue;
            }

            foreach ($finder->findInstanceOf($method->stmts ?? [], Return_::class) as $return) {
                if ($return->expr !== null) {
                    foreach (self::returned($return->expr) as $string) {
                        $report($string);
                    }
                }
            }

            foreach ($finder->findInstanceOf($method->stmts ?? [], Coalesce::class) as $coalesce) {
                foreach (self::returned($coalesce->right) as $string) {
                    $report($string);
                }
            }
        }

        return $literals;
    }

    /**
     * @return list<string>
     */
    public static function typeScriptLiterals(string $code): array
    {
        $code = self::withoutTypeScriptComments($code);
        $string = '(?:"(?<double>(?:[^"\\\\\n]|\\\\.)*)"|\'(?<single>(?:[^\'\\\\\n]|\\\\.)*)\'|`(?<template>(?:[^`\\\\]|\\\\.)*)`)';
        $patterns = [
            '/\bdefault\s*:\s*' . $string . '/',
            '/\.(?:textContent|innerText|ariaLabel|title|placeholder|alt)\s*=(?!=)\s*' . $string . '/',
            '/\bsetAttribute\(\s*["\'](?:' . implode('|', self::TEXT_ATTRIBUTES) . ')["\']\s*,\s*' . $string . '/',
            '/(?<![\w$])[\w$]*' . self::TEXT_BINDING . '\s*(?::\s*[\w$<>|\[\] ]+)?=(?![=>])\s*' . $string . '/i',
        ];
        $literals = [];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $code, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

            foreach ($matches as $match) {
                $offset = max($match['double'][1] ?? -1, $match['single'][1] ?? -1, $match['template'][1] ?? -1);
                $value = ($match['double'][0] ?? '') . ($match['single'][0] ?? '') . ($match['template'][0] ?? '');

                if (self::hasLetter(preg_replace('/\$\{[^}]*\}/', '', $value) ?? $value) && !self::isToken($value)) {
                    $literals[$offset] = self::collapse($value);
                }
            }
        }

        ksort($literals);

        return array_values($literals);
    }

    /**
     * A string token of a template expression, when it is user-facing text: a letter-bearing value of a text prop or
     * attribute key, or any literal holding an uppercase letter or a space that is not translated, not a component,
     * template or constant name, and not a list of `sy-` classes.
     *
     * @param list<Token> $tokens
     */
    private static function expressionLiteral(array $tokens, int $index): ?string
    {
        $value = self::value($tokens[$index]);
        $next = $tokens[$index + 1] ?? null;
        $previous = $tokens[$index - 1] ?? null;

        if ($next !== null && $next->test(Token::OPERATOR_TYPE, '|') && ($tokens[$index + 2] ?? null)?->test(Token::NAME_TYPE, 'trans') === true) {
            return null;
        }

        if (self::insideTransArguments($tokens, $index) || ($previous !== null && $previous->test(Token::NAME_TYPE, 'component'))) {
            return null;
        }

        if (($previous?->test(Token::PUNCTUATION_TYPE, '(') === true || $previous?->test(Token::OPERATOR_TYPE, '(') === true) && ($tokens[$index - 2] ?? null)?->test(Token::NAME_TYPE, ['component', 'constant', 'include', 'source', 'block', 'enum']) === true) {
            return null;
        }

        $key = $previous !== null && $previous->test(Token::PUNCTUATION_TYPE, ':') ? $tokens[$index - 2] ?? null : null;

        if ($key !== null && ($key->test(Token::NAME_TYPE) || $key->test(Token::STRING_TYPE)) && in_array(self::value($key), [...self::TEXT_PROPS, ...self::TEXT_ATTRIBUTES], true)) {
            return self::hasLetter($value) ? self::collapse($value) : null;
        }

        if (preg_match('/[A-Z ]/', $value) !== 1 || self::isToken($value) || str_starts_with($value, '@') || str_contains($value, '\\') || str_contains($value, '::')) {
            return null;
        }

        if (preg_match('/^\s*sy(?:-[\w-]*)?(?:\s+sy(?:-[\w-]*)?)*\s*$/', $value) === 1 || preg_match('/^[A-Z]\w*:[A-Z][\w:]*$/', $value) === 1) {
            return null;
        }

        if (self::isComparisonOperator($previous) || self::isComparisonOperator($next)) {
            return null;
        }

        return self::collapse($value) !== '' ? self::collapse($value) : json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Whether the string token sits inside the argument list of a `|trans` filter, where only keys, domains and
     * parameters go.
     *
     * @param list<Token> $tokens
     */
    private static function insideTransArguments(array $tokens, int $index): bool
    {
        $depth = 0;

        for ($i = $index - 1; $i >= 0; $i--) {
            $token = $tokens[$i];

            if ($token->test(Token::VAR_START_TYPE) || $token->test(Token::BLOCK_START_TYPE)) {
                return false;
            }

            if (in_array($token->getValue(), [')', ']', '}'], true)) {
                $depth++;
            } elseif (in_array($token->getValue(), ['(', '[', '{'], true)) {
                if ($depth === 0) {
                    return $token->getValue() === '(' && ($tokens[$i - 1] ?? null)?->test(Token::NAME_TYPE, 'trans') === true && ($tokens[$i - 2] ?? null)?->test(Token::OPERATOR_TYPE, '|') === true || self::insideTransArguments($tokens, $i);
                }

                $depth--;
            }
        }

        return false;
    }

    /**
     * @return list<string> the run of markup, split at each Twig tag, as letter-bearing text with each `{{ }}` shown
     */
    private static function markupText(string $markup): array
    {
        $texts = [];

        foreach (explode(self::BOUNDARY, $markup) as $piece) {
            $letters = preg_replace('/&#?\w+;/', '', str_replace(self::EXPRESSION, '', $piece)) ?? $piece;

            if (self::hasLetter($letters)) {
                $texts[] = self::collapse(str_replace(self::EXPRESSION, '{{ }}', $piece));
            }
        }

        return $texts;
    }

    /**
     * @param array<Node> $nodes
     *
     * @return array<int, true> the strings that are not text, by object id: array keys, comparison operands and match
     *                          conditions, anything
     *                          under a `throw`, a message's key and domain, and a literal handed straight to a call
     *                          that neither sets text nor builds it
     */
    private static function nonTextStrings(NodeFinder $finder, array $nodes): array
    {
        $excluded = [];
        $exclude = static function (?Node $node) use (&$excluded): void {
            if ($node instanceof String_ || $node instanceof InterpolatedString) {
                $excluded[spl_object_id($node)] = true;
            }
        };

        foreach ($finder->find($nodes, static fn (Node $node): bool => true) as $node) {
            if ($node instanceof ArrayItem) {
                $exclude($node->key);
            } elseif ($node instanceof Identical || $node instanceof NotIdentical || $node instanceof Equal || $node instanceof NotEqual) {
                $exclude($node->left);
                $exclude($node->right);
            } elseif ($node instanceof MatchArm) {
                foreach ($node->conds ?? [] as $condition) {
                    $exclude($condition);
                }
            } elseif ($node instanceof Throw_) {
                foreach ($finder->find([$node], static fn (Node $node): bool => $node instanceof String_ || $node instanceof InterpolatedString) as $string) {
                    $exclude($string);
                }
            } elseif (($node instanceof New_ || $node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall || $node instanceof FuncCall) && !$node->isFirstClassCallable()) {
                $name = $node instanceof New_ ? ($node->class instanceof Name ? $node->class->toString() : '') : ($node->name instanceof Identifier || $node->name instanceof Name ? $node->name->toString() : '');

                if ($node instanceof New_ ? $name === TranslatableMessage::class : in_array($name, ['trans', 't'], true)) {
                    foreach ($node->getArgs() as $position => $argument) {
                        $argumentName = $argument->name?->toString();

                        if (in_array($argumentName, ['message', 'id', 'domain'], true) || ($argumentName === null && in_array($position, [0, 2], true))) {
                            $exclude($argument->value);
                        }
                    }
                } elseif (!in_array(mb_strtolower($name), array_map(strtolower(...), self::TEXT_SETTERS), true)) {
                    foreach ($node->getArgs() as $position => $argument) {
                        if ((self::TEXT_BUILDERS[mb_strtolower($name)] ?? null) !== $position) {
                            $exclude($argument->value);
                        }
                    }
                }
            }
        }

        return $excluded;
    }

    /**
     * @return list<String_|InterpolatedString> every string in the expression, closures and nested calls included
     */
    private static function strings(NodeFinder $finder, Expr $expression): array
    {
        /** @var list<String_|InterpolatedString> */
        return $finder->find([$expression], static fn (Node $node): bool => $node instanceof String_ || $node instanceof InterpolatedString);
    }

    /**
     * @return list<String_|InterpolatedString> the strings an expression evaluates to as a whole: itself, a `??`
     *                                          fallback, a ternary or match branch, or a concatenated part
     */
    private static function returned(Expr $expression): array
    {
        return match (true) {
            $expression instanceof String_, $expression instanceof InterpolatedString => [$expression],
            $expression instanceof Coalesce => self::returned($expression->right),
            $expression instanceof Concat => [...self::returned($expression->left), ...self::returned($expression->right)],
            $expression instanceof Ternary => [...$expression->if instanceof Expr ? self::returned($expression->if) : [], ...self::returned($expression->else)],
            $expression instanceof Match_ => array_merge([], ...array_map(static fn (MatchArm $arm): array => self::returned($arm->body), $expression->arms)),
            $expression instanceof FuncCall && $expression->name instanceof Name && isset(self::TEXT_BUILDERS[$expression->name->toLowerString()]) && !$expression->isFirstClassCallable() => ($text = $expression->getArgs()[self::TEXT_BUILDERS[$expression->name->toLowerString()]] ?? null) !== null ? self::returned($text->value) : [],
            default => [],
        };
    }

    private static function phpText(Node $node): ?string
    {
        if ($node instanceof String_) {
            return self::hasLetter($node->value) && !self::isPlaceholder($node->value) ? self::collapse($node->value) : null;
        }

        if (!$node instanceof InterpolatedString) {
            return null;
        }

        $text = implode('', array_map(static fn (Node $part): string => $part instanceof InterpolatedStringPart ? $part->value : '{…}', $node->parts));

        return self::hasLetter(str_replace('{…}', '', $text)) ? self::collapse($text) : null;
    }

    /**
     * @return array<string, string> each matching file's contents under the directory, keyed by its relative path
     */
    private static function files(string $directory, string $name): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];

        foreach (Finder::create()->files()->in($directory)->name($name)->sortByName() as $file) {
            $files[str_replace('\\', '/', $file->getRelativePathname())] = $file->getContents();
        }

        return $files;
    }

    private static function withoutTypeScriptComments(string $code): string
    {
        return preg_replace_callback(
            '/"(?:[^"\\\\\n]|\\\\.)*"|\'(?:[^\'\\\\\n]|\\\\.)*\'|`(?:[^`\\\\]|\\\\.)*`|\/\/[^\n]*|\/\*.*?\*\//s',
            static fn (array $match): string => str_starts_with($match[0], '/') ? '' : $match[0],
            $code,
        ) ?? $code;
    }

    /**
     * Whether a literal reads as a token rather than text: blank, one lowercase-led word with no whitespace at all,
     * such as a Stimulus action or a placement, a CSS property name and its colon, or a client-side placeholder.
     */
    private static function isToken(string $value): bool
    {
        return mb_trim($value) === '' || preg_match('/^[a-z:-][^\s]*$/', $value) === 1 || preg_match('/^\s*-{0,2}[a-z][\w-]*:\s*$/', $value) === 1 || self::isPlaceholder($value);
    }

    /**
     * Whether a literal is the token a controller replaces with a client-side value (`__COUNT__`, `__TAG__`).
     */
    private static function isPlaceholder(string $value): bool
    {
        return preg_match('/^__[A-Z][A-Z0-9_]*__$/', $value) === 1;
    }

    private static function isComparisonOperator(?Token $token): bool
    {
        return $token instanceof Token && $token->test(Token::OPERATOR_TYPE, ['==', '!=', '<', '>', '<=', '>=', 'in', 'not in', 'is', 'is not', 'starts with', 'ends with', 'matches', 'same as']);
    }

    private static function value(Token $token): string
    {
        $value = $token->getValue();

        if (!is_string($value)) {
            throw new LogicException('A Twig text, name or string token holds no string.');
        }

        return $value;
    }

    private static function hasLetter(string $text): bool
    {
        return preg_match('/\pL/u', $text) === 1;
    }

    private static function collapse(string $text): string
    {
        return mb_trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}

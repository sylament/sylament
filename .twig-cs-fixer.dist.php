<?php

declare(strict_types=1);

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Function\IncludeFunctionRule;
use TwigCsFixer\Rules\Operator\OperatorSpacingRule;
use TwigCsFixer\Rules\Punctuation\PunctuationSpacingRule;

$config = new Config();

$config->getRuleset()
    // `{% include %}` is deliberate: ActionGroup::getTriggerTemplate() feeds it a
    // runtime template name, and the tag form is what CLAUDE.md documents.
    ->removeRule(IncludeFunctionRule::class)
    // Hash-brace spacing goes unchecked: the rule's default of 0 rejects the spaced
    // `{ id: x }` this codebase writes, and forcing 1 instead rejects the empty `{}`
    // that the spread guards rely on. `null` is "no opinion", leaving the rest of the
    // rule — `:` `,` `(` `[` spacing — enforced.
    ->overrideRule(new PunctuationSpacingRule(
        beforeOverride: ['}' => null],
        afterOverride: ['{' => null],
    ))
    // A spread binds to its operand: `...(x ? y : {})`, never `... (x ? y : {})`.
    ->overrideRule(new OperatorSpacingRule(
        afterOverride: ['...' => 0],
    ));

// Non-fixable rules stay off: VariableNameRule and NamedArgumentNameRule want
// snake_case, but props mirror the PHP API they are passed from and are camelCase
// by design (`slideOverPosition`, `extraWindowAttributes`).

return $config->setFinder(
    new Finder()
        ->in(__DIR__ . '/packages')
        ->path('#/(templates|tests)/#')
);

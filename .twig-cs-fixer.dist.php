<?php

declare(strict_types=1);

use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Delimiter\EndBlockNameRule;
use TwigCsFixer\Rules\Function\IncludeFunctionRule;
use TwigCsFixer\Rules\Function\NamedArgumentNameRule;
use TwigCsFixer\Rules\Node\ForbiddenFunctionRule;
use TwigCsFixer\Rules\Node\ValidConstantFunctionRule;
use TwigCsFixer\Rules\Operator\OperatorSpacingRule;
use TwigCsFixer\Rules\Variable\VariableNameRule;

$config = new Config();

// Naming rules cannot be auto-fixed, so they are off unless asked for — on both the
// Config and the Ruleset; enabling only one silently keeps them dormant.
$config->allowNonFixableRules();

$config->getRuleset()
    ->allowNonFixableRules()
    ->removeRule(IncludeFunctionRule::class)
    // A spread binds to its operand: `...(x ? y : {})`, never `... (x ? y : {})`.
    ->overrideRule(new OperatorSpacingRule(
        afterOverride: ['...' => 0],
    ))
    // camelCase, not the rules' snake_case default: template variables mirror the PHP
    // API they are passed from. `overrideRule` is required — both rules already ship in
    // the Twig standard, and `addRule` would leave the snake_case original in place.
    ->overrideRule(new VariableNameRule(VariableNameRule::CAMEL_CASE))
    ->overrideRule(new NamedArgumentNameRule(NamedArgumentNameRule::CAMEL_CASE))
    // A `constant()` argument is a hardcoded FQCN string that PHPStan cannot see and a
    // namespace move breaks silently. This is the only check in the toolchain that reads it.
    ->addRule(new ValidConstantFunctionRule())
    // One attribute implementation: attrs() and classes(), never twig/html-extra's
    // helpers, which are not a declared dependency and no consumer app must register.
    ->addRule(new ForbiddenFunctionRule(['html_attr', 'html_classes']))
    ->addRule(new EndBlockNameRule());

return $config->setFinder(
    new Finder()
        ->in(__DIR__ . '/packages')
        ->path('#/(templates|tests)/#')
);

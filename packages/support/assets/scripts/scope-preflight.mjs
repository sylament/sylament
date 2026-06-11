/**
 * Generates src/styles/preflight.generated.css from the installed Tailwind's
 * preflight.css, scoped to Sylament markup. Runs before every CSS build/watch, so the
 * scoped preflight is always byte-faithful to the Tailwind version in use.
 *
 * Sylament markup is identified by the `sy` marker class that every component
 * template's root element carries (exact-class matching — a host app's own `sy-*`-like
 * class names can never trigger the reset). Every rule is rewritten into two variants
 * that add zero specificity:
 *   - self:       `hr` → `hr:where(.sy)`     (the component root is itself <sel>)
 *   - descendant: `hr` → `:where(.sy) hr`    (<sel> appears inside Sylament markup)
 *
 * Adaptations (the only divergences from upstream):
 *   - `html, :host` maps onto Sylament root elements (sy- with no sy- ancestor) so its
 *     inherited properties cascade through the component subtree like the original, and
 *     its font-* declarations are dropped so components inherit the host page's font;
 *   - `body` is dropped (its margin reset is covered by the universal rule).
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import postcss from 'postcss';

const SCOPE = ':where(.sy)';
const ROOT_SCOPE = `${SCOPE}:where(:not(${SCOPE} *))`;
const DROPPED_HTML_DECLS = ['font-family', 'font-feature-settings', 'font-variation-settings'];

const here = path.dirname(fileURLToPath(import.meta.url));
const preflightPath = createRequire(import.meta.url).resolve('tailwindcss/preflight.css');
const outputPath = path.join(here, '../src/styles/preflight.generated.css');

/** Index of the first top-level combinator (space, >, +, ~), or -1. */
function topLevelCombinatorIndex(selector) {
    let depth = 0;
    let quote = null;
    for (let i = 0; i < selector.length; i++) {
        const c = selector[i];
        if (quote !== null) {
            if (c === quote && selector[i - 1] !== '\\') quote = null;
        } else if (c === '"' || c === "'") {
            quote = c;
        } else if (c === '(' || c === '[') {
            depth++;
        } else if (c === ')' || c === ']') {
            depth--;
        } else if (depth === 0 && (c === ' ' || c === '\t' || c === '>' || c === '+' || c === '~')) {
            return i;
        }
    }
    return -1;
}

/** Index of the first top-level pseudo-element (`::`), or -1. */
function topLevelPseudoElementIndex(compound) {
    let depth = 0;
    for (let i = 0; i < compound.length; i++) {
        const c = compound[i];
        if (c === '(' || c === '[') depth++;
        else if (c === ')' || c === ']') depth--;
        else if (depth === 0 && c === ':' && compound[i + 1] === ':') return i;
    }
    return -1;
}

/** `abbr[title]` → `abbr[title]:where(SCOPE)`; `::placeholder` → `:where(SCOPE)::placeholder` */
function selfVariant(selector) {
    const split = topLevelCombinatorIndex(selector);
    const head = split === -1 ? selector : selector.slice(0, split);
    const tail = split === -1 ? '' : selector.slice(split);
    const pseudo = topLevelPseudoElementIndex(head);
    const scopedHead =
        pseudo === -1 ? head + SCOPE : head.slice(0, pseudo) + SCOPE + head.slice(pseudo);
    return scopedHead + tail;
}

function descendantVariant(selector) {
    return `${SCOPE} ${selector}`;
}

const source = fs.readFileSync(preflightPath, 'utf8');
const root = postcss.parse(source);

root.walkRules((rule) => {
    const selectors = rule.selectors.map((s) => s.replace(/\s+/g, ' ').trim());

    if (selectors.includes('html')) {
        rule.selector = ROOT_SCOPE;
        rule.walkDecls((decl) => {
            if (DROPPED_HTML_DECLS.includes(decl.prop)) decl.remove();
        });
        return;
    }

    if (selectors.length === 1 && selectors[0] === 'body') {
        rule.remove();
        return;
    }

    rule.selectors = selectors.flatMap((s) => [selfVariant(s), descendantVariant(s)]);
});

const banner = `/* GENERATED FILE — do not edit. Built by scripts/scope-preflight.mjs from\n   ${path.relative(path.join(here, '..'), preflightPath)} (run \`pnpm build:css\` to regenerate). */\n\n`;

fs.writeFileSync(outputPath, banner + root.toString());
console.log(`Scoped preflight written to ${path.relative(process.cwd(), outputPath)}`);

/**
 * Rewrites a stylesheet so every rule applies only inside a `sy-` scope, at the original
 * specificity. Two callers share it: scope-preflight.mjs (Tailwind's reset, scoped to the
 * `sy` marker class) and scope-vendor-css.mjs (a redistributed vendor stylesheet, scoped to
 * the component class it skins).
 */

import postcss from 'postcss';

const KEYFRAMES_AT_RULE = /^(-[\w-]+-)?keyframes$/i;
const ANIMATION_DECLS = new Set(['animation', '-webkit-animation', 'animation-name', '-webkit-animation-name']);
const ROOT_SELECTORS = ['html', ':root', ':host'];

/** The scope's own root element: scoped, with no scoped ancestor. */
export function scopeRoot(scope) {
    return `${scope}:where(:not(${scope} *))`;
}

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

/** `abbr[title]` → `abbr[title]:where(.sy)`; `::placeholder` → `:where(.sy)::placeholder` */
export function selfVariant(selector, scope) {
    const split = topLevelCombinatorIndex(selector);
    const head = split === -1 ? selector : selector.slice(0, split);
    const tail = split === -1 ? '' : selector.slice(split);
    const pseudo = topLevelPseudoElementIndex(head);
    const scopedHead =
        pseudo === -1 ? head + scope : head.slice(0, pseudo) + scope + head.slice(pseudo);
    return scopedHead + tail;
}

export function descendantVariant(selector, scope) {
    return `${scope} ${selector}`;
}

function isKeyframeRule(rule) {
    return rule.parent?.type === 'atrule' && KEYFRAMES_AT_RULE.test(rule.parent.name);
}

/**
 * Prefixes every `@keyframes` name and every reference to one, so a redistributed stylesheet
 * cannot claim a global animation name the host page also uses.
 */
function prefixKeyframes(root, prefix) {
    const names = new Set();

    root.walkAtRules((atRule) => {
        if (!KEYFRAMES_AT_RULE.test(atRule.name)) return;

        const name = atRule.params.trim();
        names.add(name);
        atRule.params = `${prefix}${name}`;
    });

    if (names.size === 0) return;

    root.walkDecls((decl) => {
        if (!ANIMATION_DECLS.has(decl.prop.toLowerCase())) return;

        decl.value = decl.value.replace(/[\w-]+/g, (token) =>
            names.has(token) ? `${prefix}${token}` : token,
        );
    });
}

/**
 * Rewrites every rule of `css` under `scope` (a selector such as `:where(.sy-fo-file-upload)`):
 * variants 'both' (self + descendant, preflight's shape) or 'descendant' (the wrapper is always
 * an ancestor); `html`/`:root`/`:host` map onto `mapRootTo`; rules inside @keyframes are left
 * alone and, with `renameKeyframes`, the keyframe names gain that prefix and every
 * animation/animation-name declaration is rewritten to match; `dropSelectors` removes a rule
 * whose whole selector list they cover, and `dropDecls` maps a rule's selector list to the
 * declarations dropped from it.
 */
export function scopeStylesheet(
    css,
    { scope, variants = 'both', renameKeyframes = null, mapRootTo = null, dropSelectors = [], dropDecls = {} },
) {
    const root = postcss.parse(css);

    if (renameKeyframes !== null) prefixKeyframes(root, renameKeyframes);

    root.walkRules((rule) => {
        if (isKeyframeRule(rule)) return;

        const selectors = rule.selectors.map((s) => s.replace(/\s+/g, ' ').trim());
        const dropped = dropDecls[selectors.join(', ')];

        if (dropped !== undefined) {
            rule.walkDecls((decl) => {
                if (dropped.includes(decl.prop)) decl.remove();
            });
        }

        if (mapRootTo !== null && selectors.some((s) => ROOT_SELECTORS.includes(s))) {
            rule.selector = mapRootTo;
            return;
        }

        if (dropSelectors.length > 0 && selectors.every((s) => dropSelectors.includes(s))) {
            rule.remove();
            return;
        }

        rule.selectors =
            variants === 'descendant'
                ? selectors.map((s) => descendantVariant(s, scope))
                : selectors.flatMap((s) => [selfVariant(s, scope), descendantVariant(s, scope)]);
    });

    return root.toString();
}

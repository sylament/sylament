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
import { scopeRoot, scopeStylesheet } from './scope-css.mjs';

const SCOPE = ':where(.sy)';
const DROPPED_HTML_DECLS = ['font-family', 'font-feature-settings', 'font-variation-settings'];

const here = path.dirname(fileURLToPath(import.meta.url));
const preflightPath = createRequire(import.meta.url).resolve('tailwindcss/preflight.css');
const outputPath = path.join(here, '../src/styles/preflight.generated.css');

const scoped = scopeStylesheet(fs.readFileSync(preflightPath, 'utf8'), {
    scope: SCOPE,
    mapRootTo: scopeRoot(SCOPE),
    dropSelectors: ['body'],
    dropDecls: { 'html, :host': DROPPED_HTML_DECLS },
});

const banner = `/* GENERATED FILE — do not edit. Built by scripts/scope-preflight.mjs from\n   ${path.relative(path.join(here, '..'), preflightPath)} (run \`pnpm build:css\` to regenerate). */\n\n`;

fs.writeFileSync(outputPath, banner + scoped);
console.log(`Scoped preflight written to ${path.relative(process.cwd(), outputPath)}`);

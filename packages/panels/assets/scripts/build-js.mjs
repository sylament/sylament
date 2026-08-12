/**
 * Builds src/**\/*.ts into dist/ for AssetMapper consumption.
 *
 * Two shipping modes, driven by BUNDLED_ENTRIES:
 *   - default: per-file, unbundled — import specifiers ship verbatim to the
 *     browser (relative imports carry .js; bare specifiers resolve through the
 *     consuming app's importmap, e.g. @hotwired/stimulus);
 *   - bundled: entries with a dependency consumers have no importmap entry
 *     for — the dependency is inlined, its license banner composed from the
 *     installed package so it can never go stale, and framework packages are
 *     kept external.
 *
 * Usage: node scripts/build-js.mjs [--watch]
 */

import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as esbuild from 'esbuild';

/** src-relative entry → { external: bare specifiers left to the importmap, license: package whose notice heads the bundle }. */
const BUNDLED_ENTRIES = {};

const here = path.dirname(fileURLToPath(import.meta.url));
const srcDir = path.join(here, '../src');
const watch = process.argv.includes('--watch');
const require = createRequire(import.meta.url);

const sharedOptions = {
    outdir: path.join(here, '../dist'),
    outbase: srcDir,
    format: 'esm',
    sourcemap: watch ? 'inline' : 'linked',
};

function licenseBanner(packageName) {
    const packageJsonPath = require.resolve(`${packageName}/package.json`);
    const { version, license, homepage } = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    const licenseText = fs.readFileSync(path.join(path.dirname(packageJsonPath), 'LICENSE'), 'utf8');
    const copyright = licenseText.split('\n').find((line) => line.startsWith('Copyright'));
    if (!version || !license || !copyright) {
        throw new Error(`Cannot compose the license banner for ${packageName}: missing version, license, or a "Copyright" line in LICENSE.`);
    }

    return `/*! ${packageName} ${version} | ${license} License | ${copyright.trim()}${homepage ? ` | ${homepage}` : ''} */`;
}

const allEntries = fs
    .readdirSync(srcDir, { recursive: true })
    .filter((file) => file.endsWith('.ts'))
    .map((file) => file.split(path.sep).join('/'))
    .sort();

for (const bundled of Object.keys(BUNDLED_ENTRIES)) {
    if (!allEntries.includes(bundled)) {
        throw new Error(`BUNDLED_ENTRIES names ${bundled}, which does not exist under src/.`);
    }
}

const defaultEntries = allEntries.filter((file) => !(file in BUNDLED_ENTRIES));

const groups = [
    { ...sharedOptions, entryPoints: defaultEntries.map((file) => path.join(srcDir, file)) },
    ...Object.entries(BUNDLED_ENTRIES).map(([file, { external, license }]) => ({
        ...sharedOptions,
        entryPoints: [path.join(srcDir, file)],
        bundle: true,
        external,
        banner: { js: licenseBanner(license) },
    })),
];

if (watch) {
    const contexts = await Promise.all(groups.map((group) => esbuild.context(group)));
    await Promise.all(contexts.map((ctx) => ctx.watch()));
    console.log(`[build-js] watching ${allEntries.length} entries (${Object.keys(BUNDLED_ENTRIES).length} bundled)`);
} else {
    await Promise.all(groups.map((group) => esbuild.build(group)));
}

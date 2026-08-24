/**
 * Generates a scoped copy of every vendor stylesheet a component skins, into that
 * component's own workspace as `<name>.generated.css` (gitignored, rebuilt on every
 * CSS build/watch, so the redistributed copy is always byte-faithful to the installed
 * package). The shipped stylesheet is page-wide, so a bare vendor selector would style
 * a host app's element of the same name — every rule is rewritten under the component's
 * class and every global `@keyframes` name is prefixed (docs/styling.md rule 11).
 *
 * It lives in support because postcss is a support-workspace devDependency and pnpm
 * builds support before the workspaces that own the vendor packages; each entry's
 * sources are therefore resolved through a `createRequire` rooted at the owning
 * workspace's package.json.
 *
 * Usage: node scripts/scope-vendor-css.mjs
 */

import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { scopeRoot, scopeStylesheet } from './scope-css.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));

/** Output file name → { from: the workspace package.json whose node_modules resolves the sources, into: the directory the copy is written to, sources: package sub-paths in cascade order, scope: the class every rule nests under, variants/renameKeyframes: the scoper's options, license: the packages whose notices head the file }. */
const STYLESHEETS = {
    'filepond.generated.css': {
        from: '../../../forms/assets/package.json',
        into: '../../../forms/assets/src/styles',
        sources: [
            'filepond/dist/filepond.css',
            'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css',
            'filepond-plugin-image-edit/dist/filepond-plugin-image-edit.css',
        ],
        scope: ':where(.sy-fo-file-upload)',
        variants: 'descendant',
        renameKeyframes: 'sy-fo-file-upload-',
        license: ['filepond', 'filepond-plugin-image-preview', 'filepond-plugin-image-edit'],
    },
    'cropper.generated.css': {
        from: '../../../forms/assets/package.json',
        into: '../../../forms/assets/src/styles',
        sources: ['cropperjs/dist/cropper.css'],
        scope: ':where(.sy-fo-file-upload-editor)',
        variants: 'descendant',
        renameKeyframes: 'sy-fo-file-upload-editor-',
        license: ['cropperjs'],
    },
};

/** LICENSE file names vendors ship, in the order they are looked for. */
const LICENSE_FILES = ['LICENSE', 'LICENSE.md', 'license'];

function licenseBanner(require, packageNames) {
    return packageNames
        .map((packageName) => {
            const packageJsonPath = require.resolve(`${packageName}/package.json`);
            const { version, license, homepage } = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
            const licensePath = LICENSE_FILES.map((file) => path.join(path.dirname(packageJsonPath), file)).find((file) => fs.existsSync(file));
            const licenseText = licensePath === undefined ? '' : fs.readFileSync(licensePath, 'utf8');
            const copyright = licenseText.split('\n').map((line) => line.trim()).find((line) => /^Copyright\s+(\(c\)\s*)?(\d{4}(\s*[-–]\s*\d{4})?\s+)?\S/.test(line));
            if (!version || !license || !copyright) {
                throw new Error(`Cannot compose the license banner for ${packageName}: missing version, license, or a "Copyright" line in LICENSE.`);
            }

            return `/*! ${packageName} ${version} | ${license} License | ${copyright}${homepage ? ` | ${homepage}` : ''} */`;
        })
        .join('\n');
}

for (const [name, entry] of Object.entries(STYLESHEETS)) {
    const require = createRequire(path.resolve(here, entry.from));
    const sources = entry.sources.map((source) => require.resolve(source));
    const scoped = scopeStylesheet(sources.map((source) => fs.readFileSync(source, 'utf8')).join('\n'), {
        scope: entry.scope,
        variants: entry.variants,
        renameKeyframes: entry.renameKeyframes,
        mapRootTo: scopeRoot(entry.scope),
    });

    const banner = `/* GENERATED FILE — do not edit. Built by scripts/scope-vendor-css.mjs from\n${entry.sources.map((source) => `   ${source}`).join('\n')} (run \`pnpm build:css\` to regenerate). */\n${licenseBanner(require, entry.license)}\n\n`;

    const outputPath = path.join(path.resolve(here, entry.into), name);
    fs.writeFileSync(outputPath, banner + scoped);
    console.log(`Scoped vendor stylesheet written to ${path.relative(process.cwd(), outputPath)}`);
}

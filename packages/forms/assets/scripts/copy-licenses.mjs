/**
 * Copies the licence text of every package BUNDLED_ENTRIES inlines into dist/, so the
 * notice travels with the shipped bundle: Apache-2.0 §4 wants the licence redistributed
 * with derivative works, and the split `sylament/forms` publishes only dist/.
 *
 * `require.resolve` throws when the package is gone; the copy throws when its LICENSE is,
 * so a missing notice fails the build rather than reaching the tree silently.
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

/** Package → dist/ file name of its LICENSE. */
const LICENSES = {
    'tom-select': 'tom-select-LICENSE.txt',
};

const here = path.dirname(fileURLToPath(import.meta.url));
const outputDir = path.join(here, '../dist');
const require = createRequire(import.meta.url);

fs.mkdirSync(outputDir, { recursive: true });

for (const [packageName, fileName] of Object.entries(LICENSES)) {
    const source = path.join(path.dirname(require.resolve(`${packageName}/package.json`)), 'LICENSE');
    if (!fs.existsSync(source)) {
        throw new Error(`Cannot copy the licence of ${packageName}: ${source} does not exist.`);
    }
    fs.copyFileSync(source, path.join(outputDir, fileName));
}

console.log(`Copied ${Object.keys(LICENSES).length} licence file(s) to ${path.relative(process.cwd(), outputDir)}`);

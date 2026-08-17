/**
 * Copies the font files src/styles/fonts.css declares out of their installed packages
 * into dist/styles/fonts/, so the built stylesheet's relative `url()`s resolve inside
 * the shipped tree. Runs before every CSS build/watch, so the shipped woff2 is always
 * the one the installed package carries.
 *
 * AssetMapper rewrites a relative CSS url only when the target is in the mapped tree;
 * CssAssetUrlCompiler otherwise returns the path unchanged behind a log warning, so a
 * missing copy reaches the browser as a 404 rather than failing anything. Hence
 * `require.resolve`, which throws here when the package or sub-path is gone.
 */

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

/** Package sub-path → dist/styles/fonts/ file name. Each font's LICENSE ships beside it —
    the SIL Open Font License requires the notice to travel with redistributed font files. */
const FILES = {
    '@fontsource-variable/inter/files/inter-latin-wght-normal.woff2': 'inter-latin-wght-normal.woff2',
    '@fontsource-variable/inter/LICENSE': 'inter-LICENSE.txt',
};

const here = path.dirname(fileURLToPath(import.meta.url));
const outputDir = path.join(here, '../dist/styles/fonts');
const require = createRequire(import.meta.url);

fs.mkdirSync(outputDir, { recursive: true });

for (const [source, fileName] of Object.entries(FILES)) {
    fs.copyFileSync(require.resolve(source), path.join(outputDir, fileName));
}

console.log(`Copied ${Object.keys(FILES).length} font file(s) to ${path.relative(process.cwd(), outputDir)}`);

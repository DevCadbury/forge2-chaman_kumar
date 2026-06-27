#!/usr/bin/env node

/**
 * Frontend smoke test — verifies the production build output.
 * Run after `npm run build`.
 *
 * Checks:
 * 1. dist/index.html exists
 * 2. dist/index.html contains a root mount point (#root)
 * 3. dist/index.html references built JS/CSS assets
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const distDir = path.resolve(__dirname, '..', 'dist');
const indexPath = path.join(distDir, 'index.html');

let errors = [];

// 1. Check dist/index.html exists
if (!fs.existsSync(indexPath)) {
    errors.push('FAIL: dist/index.html does not exist. Did you run `npm run build`?');
} else {
    const html = fs.readFileSync(indexPath, 'utf-8');

    // 2. Check for root mount point
    if (!html.includes('id="root"') && !html.includes("id='root'")) {
        errors.push('FAIL: dist/index.html does not contain a #root mount point');
    }

    // 3. Check for built asset references (JS)
    if (!html.includes('.js')) {
        errors.push('FAIL: dist/index.html does not reference any JS assets');
    }

    // 4. Check for CSS (should be present with Tailwind)
    if (!html.includes('.css')) {
        errors.push('WARN: dist/index.html does not reference any CSS assets');
    }
}

if (errors.length > 0) {
    console.error('\n❌ Frontend smoke test failed:\n');
    errors.forEach(e => console.error('  ' + e));
    process.exit(1);
} else {
    console.log('\n✅ Frontend smoke test passed: dist/index.html exists with root mount point and built assets\n');
    process.exit(0);
}

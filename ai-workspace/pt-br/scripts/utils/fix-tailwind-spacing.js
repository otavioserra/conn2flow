#!/usr/bin/env node
/*
  fix-tailwind-suggests.js
  - detecta e substitui classes Tailwind sinalizadas pelo lint `suggestCanonicalClasses`
    * espaçamentos arbitrários: w-[500px], h-[600px]  -> w-125, h-150 (e atualiza tailwind.config.js)
    * gradientes: bg-gradient-to-r -> bg-linear-to-r (e variantes)
  Uso:
    node fix-tailwind-suggests.js --target <path> [--replace] [--apply-config]
    (se --target não fornecido, varre conn2flow e conn2flow-site)
*/

const fs = require('fs');
const path = require('path');
const child = require('child_process');

function resolveTargets(cliTarget) {
    const repoRoot = path.resolve(__dirname, '../../../../'); // conn2flow workspace root
    // if user passed explicit targets, try multiple heuristics (cwd-relative, repo-root-relative, sibling repo)
    if (cliTarget) {
        const resolved = [];
        for (const raw of cliTarget.split(',')) {
            const t = raw.trim();
            const candCwd = path.resolve(process.cwd(), t);
            if (fs.existsSync(candCwd)) { resolved.push(candCwd); continue; }
            const candRepoRoot = path.resolve(repoRoot, t);
            if (fs.existsSync(candRepoRoot)) { resolved.push(candRepoRoot); continue; }
            const candSibling = path.join(path.dirname(repoRoot), t);
            if (fs.existsSync(candSibling)) { resolved.push(candSibling); continue; }
            // fallback: keep cwd-resolved path even if missing (so caller sees the path they asked for)
            resolved.push(candCwd);
        }
        return resolved;
    }

    const siblingSite = path.join(path.dirname(repoRoot), 'conn2flow-site');
    const targets = [repoRoot];
    if (fs.existsSync(siblingSite)) targets.push(siblingSite);
    return targets;
}

// lightweight CLI args parsing (no external deps)
function parseArgs(arr) {
    const out = { _: [] };
    for (let i = 0; i < arr.length; i++) {
        const a = arr[i];
        if (a === '--replace') out.replace = true;
        else if (a === '--apply-config') out['apply-config'] = true;
        else if (a.startsWith('--target=')) out.target = a.split('=')[1];
        else if (a === '--target') out.target = arr[i + 1], i++;
        else out._.push(a);
    }
    return out;
}
const argv = parseArgs(process.argv.slice(2));
const targets = resolveTargets(argv.target);
const DO_REPLACE = !!argv.replace;
const APPLY_CONFIG = !!argv['apply-config'];

const exts = new Set(['.html', '.js', '.jsx', '.ts', '.tsx', '.vue', '.svelte', '.php', '.css']);
const ignoreDirs = new Set(['node_modules', '.git', 'dist', 'build', 'public', 'vendor']);
const ignoreFilesPattern = [/output\.css$/, /index-[a-z0-9]+\.css$/i, /assets\//, /\/public_html\//i];

function safeStat(p) {
    try { return fs.statSync(p); } catch (e) { return null; }
}

function walk(dir, out) {
    const stat = safeStat(dir);
    if (!stat) return;
    if (stat.isDirectory()) {
        for (const name of fs.readdirSync(dir)) {
            if (ignoreDirs.has(name)) continue;
            const full = path.join(dir, name);
            walk(full, out);
        }
        return;
    }
    if (!stat.isFile()) return;
    if (!exts.has(path.extname(dir))) return;
    if (ignoreFilesPattern.some(rx => rx.test(dir))) return;
    out.push(dir);
}

// regexes
const spacingRegex = /\b(?:w|h|min-w|min-h|max-w|max-h)-\[(\-?\d+)px\]\b/g; // capture px (allow negative)
const spacingClassRegex = /\b([A-Za-z0-9:-]+)-\[(\-?)(\d+)px\]\b/g; // full class detect, captures optional sign
const gradientRe = /\bbg-gradient-to-(r|l|t|b|tr|br|tl|bl)\b/g;
const opacityBracketRe = /\/(\[?(0?\.\d+)\]?)/g; // matches /[0.02] or /0.02 in class (we'll handle bracketed form)

const filesToProcess = new Set();
for (const t of targets) {
    if (!fs.existsSync(t)) continue;
    const list = [];
    walk(t, list);
    for (const f of list) filesToProcess.add(f);
}

if (filesToProcess.size === 0) {
    console.log('Nenhum arquivo fonte encontrado nos targets:', targets.join(', '));
    process.exit(0);
}

// Collect spacing usages, gradient usages, opacity uses and shrink uses
const spacingValues = new Map(); // px -> Set(files)
const gradientFiles = new Set();
const opacityFiles = new Set();
const shrinkFiles = new Set();
for (const file of filesToProcess) {
    let content;
    try { content = fs.readFileSync(file, 'utf8'); } catch (e) { continue; }
    let m;
    while ((m = spacingClassRegex.exec(content)) !== null) {
        const sign = m[2];
        const px = m[3];
        if (!spacingValues.has(px)) spacingValues.set(px, new Set());
        spacingValues.get(px).add(file);
    }
    // use match() to avoid global-regex .test() pitfalls
    if (content.match(gradientRe)) gradientFiles.add(file);

    // detect bracketed / fractional opacity usages (e.g. /[0.02] or /0.02)
    if (content.match(opacityBracketRe) || /\/0\.\d+/.test(content)) opacityFiles.add(file);

    // detect legacy flex-shrink utility usages
    if (/\bflex-shrink-0\b/.test(content)) shrinkFiles.add(file);
}

// Prepare spacing mapping
const spacingMapping = {};
[...spacingValues.keys()].sort((a, b) => a - b).forEach(px => {
    const n = Number(px);
    const key = n % 4 === 0 ? String(n / 4) : String(n);
    spacingMapping[key] = `${px}px`;
});

// Prepare gradient mapping
const gradientMapping = {
    'bg-gradient-to-r': 'bg-linear-to-r',
    'bg-gradient-to-l': 'bg-linear-to-l',
    'bg-gradient-to-t': 'bg-linear-to-t',
    'bg-gradient-to-b': 'bg-linear-to-b',
    'bg-gradient-to-tr': 'bg-linear-to-tr',
    'bg-gradient-to-br': 'bg-linear-to-br',
    'bg-gradient-to-tl': 'bg-linear-to-tl',
    'bg-gradient-to-bl': 'bg-linear-to-bl'
};

// Report findings
if (spacingValues.size === 0 && gradientFiles.size === 0 && opacityFiles.size === 0 && shrinkFiles.size === 0) {
    console.log('Nada a corrigir (nenhuma classe detectada).');
    process.exit(0);
}

console.log('\nResumo de deteções:');
if (spacingValues.size) {
    console.log('- Espaçamentos arbitrários encontrados:');
    spacingValues.forEach((set, px) => {
        console.log(`  - ${px}px: ${set.size} arquivo(s)`);
    });
    console.log('\nSugestão spacing -> theme.extend.spacing:');
    console.log(JSON.stringify(spacingMapping, null, 2));
}
if (gradientFiles.size) console.log(`- Gradientes não-canônicos em ${gradientFiles.size} arquivo(s)`);
if (opacityFiles.size) console.log(`- Opacities em forma de fração/bracketed detectadas em ${opacityFiles.size} arquivo(s)`);
if (shrinkFiles.size) console.log(`- Uso legado de \`flex-shrink-0\` detectado em ${shrinkFiles.size} arquivo(s)`);

if (!DO_REPLACE) {
    console.log('\nRode com --replace para aplicar as mudanças (isso inclui opacities e flex-shrink).');
    process.exit(0);
}

// APPLY REPLACEMENTS
console.log('\nAplicando substituições...');
for (const file of filesToProcess) {
    let content;
    try { content = fs.readFileSync(file, 'utf8'); } catch (e) { continue; }
    let updated = content;

    // spacing (handle optional negative inside brackets and responsive/state prefixes)
    updated = updated.replace(spacingClassRegex, (match, prefix, sign, px) => {
        const n = Number(px);
        const key = n % 4 === 0 ? String(n / 4) : String(n);
        const lastColon = prefix.lastIndexOf(':');
        const mod = lastColon >= 0 ? prefix.slice(0, lastColon + 1) : '';
        const util = lastColon >= 0 ? prefix.slice(lastColon + 1) : prefix;
        const negativePrefix = sign === '-' ? '-' : '';
        return `${mod}${negativePrefix}${util}-${key}`;
    });

    // gradients
    updated = updated.replace(gradientRe, (m) => {
        return gradientMapping[m] || m;
    });

    // shrink utility canonicalization: flex-shrink-0 -> shrink-0
    updated = updated.replace(/\bflex-shrink-0\b/g, 'shrink-0');

    // opacity bracket -> canonical (e.g. /[0.02] -> /2)
    updated = updated.replace(/\/\[(0?\.\d+)\]/g, (m, frac) => {
        const num = Math.round(parseFloat(frac) * 100);
        return `/${num}`;
    });
    // also handle non-bracketed fractional forms inside classes (rare): /0.02 -> /2
    updated = updated.replace(/\/(0?\.\d+)/g, (m, frac) => {
        const num = Math.round(parseFloat(frac) * 100);
        return `/${num}`;
    });

    if (updated !== content) {
        fs.writeFileSync(file, updated, 'utf8');
        console.log('Atualizado:', path.relative(process.cwd(), file));
    }
}

// apply-config: merge spacing tokens into target tailwind.config.js files
if (APPLY_CONFIG && Object.keys(spacingMapping).length) {
    for (const t of targets) {
        const cfg = path.join(t, 'tailwind.config.js');
        if (!fs.existsSync(cfg)) continue;
        const orig = fs.readFileSync(cfg, 'utf8');
        const bak = cfg + '.bak';
        if (!fs.existsSync(bak)) fs.writeFileSync(bak, orig, 'utf8');

        const spacingEntries = Object.entries(spacingMapping).map(([k, v]) => `        '${k}': '${v}'`).join(',\n');
        let updated = orig;

        if (/spacing\s*:\s*{/.test(orig)) {
            updated = orig.replace(/(spacing\s*:\s*{)([\s\S]*?)(\n\s*}\s*)/, (all, open, inner, close) => {
                const innerTrim = inner.trim();
                const merged = `${open}\n${innerTrim ? innerTrim.replace(/,?\s*$/, ',') + '\n' : ''}${spacingEntries}\n      }`;
                return merged + close;
            });
        } else if (/extend\s*:\s*{/.test(orig)) {
            updated = orig.replace(/(extend\s*:\s*{)/, `$1\n      spacing: {\n${spacingEntries}\n      },`);
        } else if (/theme\s*:\s*{/.test(orig)) {
            updated = orig.replace(/(theme\s*:\s*{)/, `$1\n    extend: {\n      spacing: {\n${spacingEntries}\n      }\n    },`);
        } else {
            updated = orig + `\n// spacing tokens added by fix-tailwind-suggests.js\nmodule.exports.theme = module.exports.theme || {};\nmodule.exports.theme.extend = module.exports.theme.extend || {};\nmodule.exports.theme.extend.spacing = Object.assign(module.exports.theme.extend.spacing || {}, ${JSON.stringify(spacingMapping, null, 2)});\n`;
        }

        if (updated !== orig) {
            fs.writeFileSync(cfg, updated, 'utf8');
            console.log('Atualizado tailwind.config.js:', cfg);
        } else {
            console.log('Não foi possível atualizar automaticamente:', cfg);
        }
    }
}

console.log('\nConcluído. Recomendo rodar o build do Tailwind/Assets.');
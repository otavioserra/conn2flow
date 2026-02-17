#!/usr/bin/env node
// wrapper -> use the merged script
const child = require('child_process');
const path = require('path');

const merged = path.join(__dirname, 'fix-tailwind-spacing.js');
const args = process.argv.slice(2);

console.log('Este arquivo é um wrapper. Chamando o script unificado: fix-tailwind-suggests.js');
const res = child.spawnSync(process.execPath, [merged, ...args], { stdio: 'inherit' });
process.exit(res.status || 0);


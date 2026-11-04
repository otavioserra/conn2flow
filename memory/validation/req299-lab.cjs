/** REQ-299: runner restrito ao Lab snapphoton.local. Nenhum cookie vai para os logs. */
const { spawnSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const out = path.join(root, 'temp/req299');
fs.mkdirSync(out, { recursive: true });
function remote(modo, dados = {}) {
  const payload = Buffer.from(JSON.stringify({ modo, ...dados })).toString('base64');
  const input = `<?php $entrada=json_decode(base64_decode('${payload}'),true); ?>` + fs.readFileSync(path.join(__dirname, 'req299-lab.php'), 'utf8');
  const r = spawnSync('ssh', ['-o', 'BatchMode=yes', 'lab', 'sudo -u snapphoton php -d display_errors=stderr'], { input, encoding: 'utf8', timeout: 60000 });
  if (r.status !== 0) throw new Error(r.stderr || r.stdout || 'SSH failed');
  if (modo === 'publish') fs.writeFileSync(path.join(out, 'publish.log'), r.stdout);
  return JSON.parse(r.stdout.trim().split(/\r?\n/).at(-1));
}
module.exports = { remote, root, out };
if (require.main === module) {
  const mode = process.argv[2] || 'snapshot';
  const result = remote(mode);
  fs.writeFileSync(path.join(out, mode + '.json'), JSON.stringify(result, null, 2));
  console.log(JSON.stringify(mode === 'snapshot' ? { usuarios: result.usuarios, perfis: result.perfis, paginas: result.paginas, cota_admin: result.cota_admin } : result));
}

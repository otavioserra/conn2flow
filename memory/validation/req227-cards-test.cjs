const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '../..');

class Element {
  constructor(tag) { this.tag = tag; this.attrs = {}; this.children = []; }
  setAttribute(key, value) { this.attrs[key] = value; }
  appendChild(child) { this.children.push(child); }
}
const container = new Element('container');
const window = {};
const sandbox = {window, document: {
  getElementById: () => container, createElement: tag => new Element(tag),
}, console: {log() {}}};
vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(root, 'gestor/assets/dashboard/dashboard-3d-config.js'), 'utf8'), sandbox);
const cardsFile = process.argv[2] || path.join(root, 'gestor/assets/dashboard/dashboard-3d-cards.js');
vm.runInContext(fs.readFileSync(cardsFile, 'utf8'), sandbox);
window.Dashboard3DConfig.prism.enabled = false;
window.Dashboard3DCards.createCards([
  {id: 'admin-paginas', nome: 'Páginas', grupo: 'editor', thumbnail: '/painel/assets/modulos/covers/admin-paginas.webp?v=1'},
  {id: 'sem-capa', nome: 'Sem capa', grupo: 'editor'},
], [{id: 'editor', nome: 'Editor'}]);
const [covered, fallback] = container.children;
const thumb = covered.children.find(child => child.attrs.class === 'card-thumbnail');
assert.ok(thumb, 'Installed cover appears even with the global placeholder option disabled');
assert.equal(thumb.attrs.width, thumb.attrs.height, 'Square illustration keeps its aspect ratio');
assert.match(thumb.attrs.material, /src: url\(\/painel\/assets\/modulos\/covers\/admin-paginas.webp\?v=1\)/);
assert.ok(!fallback.children.some(child => child.attrs.class === 'card-thumbnail'), 'Module without cover does not request a placeholder');
assert.equal(fallback.children[2].attrs.position, '-0.7 0.35 0.05', 'Existing icon position preserved');
assert.equal(covered.children[4].attrs.position, '0.15 -0.25 0.05', 'Title moves below installed cover');
console.log(JSON.stringify({checks: 6, result: 'pass'}));

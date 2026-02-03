# Dashboard 3D - Tarefas e Documentação

## Status Atual

**Data:** 2026-02-02
**Tecnologias:** A-Frame 1.7.1, aframe-orbit-controls 1.3.2, aframe-look-at-component 1.0.0, GSAP 3.12.2, THREE.js (embutido no A-Frame)

---

## ✅ Tarefas Concluídas

### 11. Correção Billboard-Camera com Orbit-Controls (2026-02-02)
- [x] **Problema identificado:** Cards olhavam para a esfera (centro) em vez do observador
- [x] **Causa raiz:** Estava usando `cameraEl.object3D` que sempre fica em (0,0,0)
- [x] **Descoberta:** O orbit-controls move `cameraEl.getObject3D('camera')`, não o `object3D`
- [x] **Solução:** Usar `this.threeCamera = cameraEl.getObject3D('camera')` para obter a câmera THREE.js
- [x] **Posição correta:** `this.threeCamera.position` contém a posição real do observador
- [x] **Componente atualizado:** `billboard-camera` em `dashboard-3d-geometry.js`

### 10. Correções Estruturais da Câmera (2026-02-02)
- [x] **Removido camera-rig** - Câmera agora é direto sem wrapper, conforme documentação oficial do orbit-controls
- [x] **Estrutura simplificada** - `<a-entity id="main-camera" camera orbit-controls="...">` sem parent rig
- [x] **restoreControls()** - Nova função em Camera.js para restaurar controles após animações
- [x] **closeActionModal melhorado** - Chama `restoreControls()` com delay de 50ms ao fechar modal
- [x] **Referência cameraRig removida** - Código simplificado em Dashboard3DCamera

### 9. Correções de Bugs nos Testes (2026-02-02)
- [x] **Cards apontando para esfera:** Corrigido com componente `billboard-camera` customizado
- [x] **Modal quebrando controles:** `zoomToCard` corrigido com distância menor e `saveState()` no onComplete
- [x] **Look-at perdendo referência:** Componente `billboard-camera` usa `tick()` com `getWorldPosition()`
- [x] **Volumes prismáticos 3D:** Reescrito com THREE.BufferGeometry (6 vértices, 8 faces triangulares)
- [x] **Picsum 405 error:** Thumbnails desabilitados por padrão, URL alternativa para placehold.co
- [x] **Distribuição prismática:** `calculatePrismPosition()` reescrita para distribuir em volume 3D real

### 8. Volumes Prismáticos
- [x] **Sistema de distribuição prismática** - Cards distribuídos em volumes triangulares
- [x] **Configuração completa** - `CONFIG.prism` com todas as opções
- [x] **Funções de cálculo:**
  - `calculatePrismPosition()` - Calcula posição do card no prisma
  - `calculateLegacyPosition()` - Fallback para distribuição antiga
- [x] **Debug visual** - Toggle com tecla `P` para visualizar prismas
- [x] **Métodos de geometria:**
  - `createDebugPrisms()` - Cria visualização dos prismas
  - `removeDebugPrisms()` - Remove visualização
  - `toggleDebugPrisms()` - Alterna modo debug

### 9. UI - Melhorias Finais
- [x] **Caixa Grupos** - Inicia escondida (class="hidden")
- [x] **Atalho P** - Adicionado na lista de atalhos de teclado

### Cards - Ajustes Finais
- [x] **Botões de Docs/Manual:** Remover dos cards, passar para o modal de ações (Listar / Adicionar)

## 📁 Arquivos Principais

### JavaScript
1. `dashboard-3d-config.js` 
    - Configurações centralizadas: cores, dimensões, câmera, animações, ícones
    - Funções dinâmicas para escalabilidade (getRadius, getDistanceFromRing, getInitialZ)
    - 16 cores de segmentos para suportar mais grupos
    - Configuração de volumes prismáticos (CONFIG.prism)
2. `dashboard-3d-camera.js` 
    - Controles de câmera: zoom, reset, focus
    - Animações GSAP com padrão correto (objetos clonados, onUpdate, onComplete)
    - Auto-rotação e fullscreen
3. `dashboard-3d-geometry.js`
    - Esfera central com anéis decorativos
    - Tubos verticais conectando esfera ao anel (raio dinâmico)
    - Segmentos do anel com labels (raio dinâmico)
    - Partículas de fundo
    - Componente custom-line
    - Funções de debug para prismas (createDebugPrisms, toggleDebugPrisms)
4. `dashboard-3d-cards.js`
    - Criação dos cards com thumbnail e botões de ação
    - Suporte a caracteres UTF-8 (função preserveAccents)
    - Conexões entre cards e anel (raio dinâmico)
    - Distribuição prismática (calculatePrismPosition)
    - Botões de Documentação e Manual
5. `dashboard-3d-ui.js`
    - Todos os event listeners
    - Tooltip, modal de ações, menu pizza
    - Legenda dos grupos
    - Handlers de teclado (incluindo P para debug prismas)
6. `dashboard-3d-main.js`
    - Orquestrador principal
    - Carrega dados dos módulos
    - Coordena inicialização de todos os módulos

### Layouts (PT-BR e EN)
- `gestor/modulos/dashboard/resources/pt-br/layouts/layout-administrativo-do-gestor-3d/`
- `gestor/modulos/dashboard/resources/en/layouts/layout-administrativo-do-gestor-3d/`

### Componentes (PT-BR e EN)
- `gestor/modulos/dashboard/resources/pt-br/components/dashboard-3d/`
- `gestor/modulos/dashboard/resources/en/components/dashboard-3d/`

### Páginas (PT-BR e EN)
- `gestor/modulos/dashboard/resources/pt-br/pages/dashboard-3d/`
- `gestor/modulos/dashboard/resources/en/pages/dashboard-3d/`

---

## 🔧 Configuração Atual (CONFIG)

```javascript
const CONFIG = {
    ring: {
        radius: 6,
        height: 1.5,
        tubeRadius: 0.15
    },
    sphere: {
        radius: 1.2,
        segments: 32
    },
    camera: {
        initialPosition: { x: 0, y: 8, z: 20 },
        minDistance: 8,
        maxDistance: 50
    },
    card: {
        width: 2.5,
        height: 1.5,
        distance: 8
    }
};
```

---

## 📚 CDNs Utilizados

```html
<!-- A-Frame -->
<script src="https://aframe.io/releases/1.7.1/aframe.min.js"></script>

<!-- Orbit Controls (supermedium/superframe) -->
<script src="https://unpkg.com/aframe-orbit-controls@1.3.2/dist/aframe-orbit-controls.min.js"></script>

<!-- GSAP (animações) -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
```

---

## 🔍 OrbitControls - API Disponível

### Propriedades Úteis
- `controls.target` - Vector3 do ponto focal
- `controls.enabled` - Habilitar/desabilitar controles
- `controls.autoRotate` - Rotação automática
- `controls.autoRotateSpeed` - Velocidade da rotação
- `controls.minDistance` / `controls.maxDistance` - Limites de zoom
- `controls.enableDamping` - Inércia nos movimentos

### Métodos Disponíveis
- `controls.update()` - Atualizar estado (obrigatório após mudanças manuais)
- `controls.saveState()` - Salvar estado atual
- `controls.reset()` - Restaurar estado salvo
- `controls.getDistance()` - Obter distância atual da câmera ao target

### ⚠️ Métodos NÃO Disponíveis (internos)
- ~~`controls.dollyIn()`~~ - Não existe na API pública
- ~~`controls.dollyOut()`~~ - Não existe na API pública

---

## 📝 Notas de Implementação

### Zoom Manual (substituindo dollyIn/dollyOut)
```javascript
function zoomCamera(delta) {
    const controls = getOrbitControls();
    if (!controls || !mainCamera) return;

    const cameraPos = mainCamera.object3D.position;
    const target = controls.target;
    
    const currentDistance = cameraPos.distanceTo(target);
    const newDistance = Math.max(
        CONFIG.camera.minDistance,
        Math.min(CONFIG.camera.maxDistance, currentDistance + delta)
    );

    const direction = new THREE.Vector3();
    direction.subVectors(cameraPos, target).normalize();

    const newPos = new THREE.Vector3();
    newPos.copy(target).add(direction.multiplyScalar(newDistance));
    
    cameraPos.copy(newPos);
    controls.update();
}
```

### Animação com GSAP sem travar controles
```javascript
// Desabilitar controles durante animação
controls.enabled = false;

const tl = gsap.timeline({
    onComplete: () => {
        controls.enabled = true;
        controls.update();
        controls.saveState(); // Importante: salvar estado após animação
    }
});

// Animar posições...
```

### Restaurar controles após modal
```javascript
// Em closeActionModal
Camera.restoreControls(); // Restaura controles após 50ms delay
```

---

## 🎯 Próximos Passos

1. ~~Testar correções de zoom e reset~~ ✅
2. ~~Corrigir posição da esfera~~ ✅
3. ~~Corrigir conexão dos tubos~~ ✅
4. ~~Implementar billboard nos cards~~ ✅
5. ~~Adicionar volumes prismáticos~~ ✅
6. ~~UI refinements~~ ✅
7. **Mover botões Doc/Manual para modal de ações**
8. **Refinar sensibilidade dos controles de mouse**

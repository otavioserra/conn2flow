---
name: 'Spec-Driven Python Tests'
description: 'Use ao editar testes Python em repositórios orientados por especificação.'
applyTo: 'tests/**/*.py'
---

- Ancore cada teste novo ao sdd numerado e ao batch ativo.
- Prefira testes deterministas e no menor slice que prove o contrato.
- Quando houver validação focada do batch, rode esse subconjunto antes da suíte completa.
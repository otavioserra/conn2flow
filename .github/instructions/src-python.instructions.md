---
name: 'Spec-Driven Python Source'
description: 'Use ao editar código Python em repositórios orientados por especificação.'
applyTo: 'src/**/*.py'
---

- Antes de mudar comportamento, releia o sdd numerado e o batch que controlam esse slice.
- Preserve os padrões técnicos já existentes do projeto em vez de refatorar amplamente sem necessidade.
- Se a mudança implicar requisito novo ou contrato diferente, atualize primeiro o fluxo SDD apropriado.
- Depois da primeira edição substantiva, rode a menor validação automatizada capaz de falsificar a mudança.
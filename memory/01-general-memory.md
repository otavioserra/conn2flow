# Memory Mechanics & Lifecycle

MDD (Memory Driven Development) organiza memória persistente em quatro camadas. Uma especificação continua normativa; o contexto verificável permite aplicar o contrato vigente sem repetir toda a história.

| Camada | Conteúdo | Localização |
| --- | --- | --- |
| Episódica | Requisições, execução, validações, handoffs, sessões e relatos datados | human-requests/, implementation/, validation/, handoffs/, sessions/, reports/ |
| Semântica / normativa | Arquitetura, SPEC, decisões, política e contratos aprovados | documentos raiz, decisions/, change-requests/ |
| Procedural | Procedimentos reutilizáveis e armadilhas operacionais | .gemini/skills/; process/ como runbooks |
| Raw | Notas de trabalho, observações intermediárias e artefatos livres de modelos | raw/active/; raw/archive/ |

## Ciclo de vida

Captação na sessão → retenção ativa → poda preventiva → arquivamento dual.
Registre origem, data, estado e ligação com requisição/lote. Promova fatos confirmados para episódios; regras recorrentes aprovadas para skills ou contratos. Observações raw nunca têm autoridade normativa por si mesmas.

Na retenção, mantenha a janela ativa e os tetos da [política](02-policy.md). Em 50 KB ou 200 linhas em memória de execução, emita alerta e planeje destilação; a manutenção preserva originais antes de reduzir o ativo. Nunca pode memória saudável só para fechar sessão. O limiar legado de 75 KB / 300 linhas não autoriza exceder o teto ativo MDD de 50 KB.

No arquivamento dual, preserve bytes do original em archive/original/ e escreva síntese em archive/compacted/, com vínculo ao original, procedência e lacunas explícitas. Atualize os índices e repare links na mesma operação. Históricos legados já arquivados permanecem no lugar, indexados, até migração autorizada; não são reescritos em massa.

## Navegação e uso de contexto

Leia index.md primeiro. Escolha por ID, resumo e status; use compacted/ para varredura histórica e original/ para comprovar detalhes. Carregue documentos densos só quando a decisão depender deles. Memória desatualizada é marcada e corrigida com referência ao código, nunca tratada como fato atual.

Raw armazena notas e resultados observáveis úteis ao trabalho, sem credenciais, dados pessoais desnecessários ou transcrição de raciocínio privado. Registre somente sínteses de decisões e evidências compartilháveis. Fechada a frente, promova o sinal útil e arquive os artefatos permitidos.

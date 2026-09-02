# Reports Index

Relatórios de sessão, do mais recente para o mais antigo. Convenções e critério de quando escrever
em [README.md](README.md).

Máximo de 10 correntes; os anteriores vão para `archive/` e permanecem listados aqui em uma linha.

## Correntes

| Data | Relatório | Lotes | Em uma linha |
| --- | --- | --- | --- |
| 2026-09-02 | [Paridade visual entre o editor e a página publicada](REPORT-2026-09-02-paridade-visual-tailwind.md) | BATCH-158, BATCH-160, BATCH-161 | Seis defeitos por trás de "três renderizações diferentes": o editor filtrava a captura de CSS contra folhas que o runtime não recebe, e isso apagava em silêncio as regras dos templates inseridos. Publicada saiu de 99 para 5 classes sem regra; 24 tags de CDN eliminadas; uma correção foi barrada — corretamente — pelo contrato do CR-002. |

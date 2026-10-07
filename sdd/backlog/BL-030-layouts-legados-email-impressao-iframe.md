# BL-030 — Aposentar ou marcar como legado os layouts de e-mail, impressão e iframe

- **Tipo**: Architecture/Maintainability
- **Status**: ICEBOX
- **Severidade sugerida**: BAIXA
- **Origem**: Humano, 2026-10-07, ao ver a lista de layouts do módulo "Páginas de Lousa" (REQ-253): "esse é um legado lá do início… agora eu acredito que não tem nenhum".
- **Componentes**: `gestor/resources/<idioma>/layouts.json` (`layout-emails`, `layout-impressao`, `layout-iframes`), `gestor/bibliotecas/comunicacao.php`, `gestor/gestor.php`, `gestor/modulos/perfil-usuario/perfil-usuario.php`.

## Levantamento (2026-10-07)

Os três não estão sem uso; nenhum deles é layout de página de site.

| Layout | Páginas que usam | Uso no código |
|---|---|---|
| `layout-emails` | nenhuma, nas duas instalações locais | `comunicacao.php` monta o e-mail com ele (e com os componentes `layout-emails-assinatura` e `hosts-layout-emails-assinatura`); `perfil-usuario.php` usa a assinatura |
| `layout-impressao` | 1 por idioma (`pagina-de-impressao`, do sistema) | página registrada em `resources/<idioma>/pages.json` |
| `layout-iframes` | nenhuma | `gestor.php` usa como layout de página aberta em iframe quando ela não é Tailwind, e como reserva quando o `layout-iframe-tailwindcss` vem vazio |

Contagem feita nos bancos das instalações locais (`conn2flow.local` e `v3.1-conn2flow.local`). **Produção não foi consultada.**

## Problema

Esses layouts aparecem nas listas de escolha de layout dos módulos de página (`admin-paginas`, `publisher-pages`, `dashboard-pages`) ao lado dos layouts de site, embora não sirvam para uma página pública.

## Caminhos possíveis

1. **Só esconder das listas**: um marcador no layout (tipo ou finalidade: site, sistema, e-mail) e os módulos de página listam só os de site. Não remove nada; menor risco.
2. **Marcar como legado**: além de esconder, selo de legado no `admin-layouts` e na documentação.
3. **Remover**: só depois de trocar os três usos no código (e-mail, impressão, iframe sem Tailwind) e de conferir produção.

Sugestão do Executor: começar pelo 1.

## A decidir

- Se a impressão e o iframe sem Tailwind ainda têm uso real em produção.
- Se o e-mail deve continuar sendo um "layout" ou virar recurso próprio da biblioteca de comunicação.

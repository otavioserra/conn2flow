<?php

declare(strict_types=1);

namespace Conn2Flow\Cli\Support\Docs;

/**
 * Classes Tailwind do conteúdo das docs (req-178), no visual do conn2flow-site: fundo
 * azul-marinho, destaque ciano rgb(29,170,198), texto claro. Ficam explícitas no HTML do
 * recurso para o compilador de CSS do projeto enxergá-las.
 */
final class DocsTheme
{
    public const ELEMENTS = [
        'h1' => 'scroll-mt-24 text-3xl md:text-4xl font-black tracking-tight text-white mb-6',
        'h2' => 'scroll-mt-24 text-2xl font-bold text-white mt-12 mb-4 pb-2 border-b border-white/10',
        'h3' => 'scroll-mt-24 text-xl font-semibold text-white mt-8 mb-3',
        'h4' => 'text-lg font-semibold text-white mt-6 mb-2',
        'p' => 'text-gray-300 leading-7 my-4',
        'ul' => 'list-disc pl-6 my-4 space-y-2 text-gray-300 marker:text-[rgb(29,170,198)]',
        'ol' => 'list-decimal pl-6 my-4 space-y-2 text-gray-300 marker:text-[rgb(29,170,198)]',
        'li' => 'leading-7',
        'a' => 'text-[rgb(29,170,198)] underline decoration-[rgb(29,170,198)]/40 underline-offset-4 hover:decoration-[rgb(29,170,198)] transition-colors',
        'strong' => 'font-semibold text-white',
        'em' => 'italic',
        'code' => 'rounded bg-white/10 px-1.5 py-0.5 font-mono text-[0.875em] text-[rgb(125,220,240)]',
        'pre' => 'overflow-x-auto rounded-xl border border-white/10 bg-[rgb(13,22,38)] p-4 text-sm leading-6',
        'pre code' => 'font-mono text-gray-200',
        'blockquote' => 'my-6 border-l-4 border-[rgb(29,170,198)]/60 pl-4 text-gray-400 italic',
        'table' => 'w-full text-left text-sm',
        'thead' => 'border-b border-white/20',
        'th' => 'px-3 py-2 font-semibold text-white',
        'td' => 'px-3 py-2 align-top text-gray-300 border-t border-white/5',
        'hr' => 'my-10 border-white/10',
        'img' => 'my-6 rounded-xl border border-white/10',
    ];

    public const CODE_FRAME = 'relative group my-6';
    public const COPY_BUTTON = 'absolute top-2 right-2 rounded-md border border-white/10 bg-white/5 p-1.5 text-gray-400 opacity-0 group-hover:opacity-100 hover:text-white transition';
    public const COPY_ICON = '<svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    public const TABLE_FRAME = 'my-6 overflow-x-auto rounded-xl border border-white/10';
    public const CALLOUT_BODY = 'text-sm text-gray-200';

    public const CALLOUTS = [
        'NOTE' => ['box' => 'my-6 rounded-xl border border-sky-400/30 bg-sky-400/10 px-4 py-3', 'title' => 'text-xs font-bold uppercase tracking-wider text-sky-300'],
        'TIP' => ['box' => 'my-6 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3', 'title' => 'text-xs font-bold uppercase tracking-wider text-emerald-300'],
        'IMPORTANT' => ['box' => 'my-6 rounded-xl border border-violet-400/30 bg-violet-400/10 px-4 py-3', 'title' => 'text-xs font-bold uppercase tracking-wider text-violet-300'],
        'WARNING' => ['box' => 'my-6 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-3', 'title' => 'text-xs font-bold uppercase tracking-wider text-amber-300'],
        'CAUTION' => ['box' => 'my-6 rounded-xl border border-rose-400/30 bg-rose-400/10 px-4 py-3', 'title' => 'text-xs font-bold uppercase tracking-wider text-rose-300'],
    ];

    public const TOC_LINK = 'block py-1 text-gray-400 hover:text-white transition-colors';
    public const TOC_LINK_L3 = 'block py-1 pl-3 text-gray-500 hover:text-white transition-colors';
    public const NAV_CARD = 'flex-1 rounded-xl border border-white/10 bg-white/5 p-4 hover:border-[rgb(29,170,198)]/60 transition-colors';
    public const NAV_LABEL = 'text-xs uppercase tracking-wider text-gray-500';
    public const NAV_TITLE = 'mt-1 font-semibold text-white';
    public const BADGE = 'inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-300';
    public const CODE_PATH = 'not-prose mb-6 flex flex-wrap items-center gap-2 text-sm';
    public const CODE_PATH_LABEL = 'text-xs uppercase tracking-wider text-gray-500';
    public const SOURCE_LINK = 'font-mono text-xs text-gray-400 hover:text-[rgb(29,170,198)] transition-colors';
}

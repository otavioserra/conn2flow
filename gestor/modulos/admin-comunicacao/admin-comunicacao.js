/**
 * Painel de comunicação (REQ-304 / BATCH-313).
 *
 * Fila & histórico (listagem paginada, filtros, preview isolado, reenvio e cancelamento) e
 * configurações da fila + redirecionamento de teste. Todo texto visível vem de atributos data-*
 * preenchidos com variáveis do sistema — nenhum literal de interface mora neste arquivo.
 *
 * O preview do HTML enviado vai para um iframe `sandbox=""` via `srcdoc`: sem scripts, sem
 * formulários e sem a origem do painel, então o conteúdo da mensagem não alcança a sessão.
 */
(function iniciarPainelComunicacao() {
    'use strict';

    // O marcador `<!-- pagina#js -->` fica no <head> dos layouts do gestor (REQ-038).
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarPainelComunicacao, { once: true });
        return;
    }

    var painel = document.getElementById('admin-comunicacao-painel');
    if (!painel) return;

    var rotulos = painel.dataset;

    var estado = {
        pagina: 1,
        paginas: 1,
        total: 0,
        itens: [],
        aberto: null,
        buscaTimer: null,
        config: lerJson(rotulos.config, {}),
        canais: lerJson(rotulos.canais, []),
        disponivel: rotulos.tabelaDisponivel === '1'
    };

    var el = {
        mensagem: document.getElementById('com-mensagem'),
        banner: document.getElementById('com-banner'),
        bannerTexto: document.getElementById('com-banner-texto'),
        corpo: document.getElementById('com-tabela-corpo'),
        info: document.getElementById('com-paginacao-info'),
        anterior: document.getElementById('com-pagina-anterior'),
        proxima: document.getElementById('com-pagina-proxima'),
        atualizar: document.getElementById('com-btn-atualizar'),
        busca: document.getElementById('com-filtro-busca'),
        status: document.getElementById('com-filtro-status'),
        canal: document.getElementById('com-filtro-canal'),
        de: document.getElementById('com-filtro-de'),
        ate: document.getElementById('com-filtro-ate'),
        statEnviados: document.getElementById('com-stat-enviados'),
        statFila: document.getElementById('com-stat-fila'),
        statFalhas: document.getElementById('com-stat-falhas'),
        statLimite: document.getElementById('com-stat-limite'),
        formConfig: document.getElementById('com-form-config'),
        cfgModo: document.getElementById('com-cfg-modo'),
        cfgRate: document.getElementById('com-cfg-rate'),
        cfgTentativas: document.getElementById('com-cfg-tentativas'),
        cfgRetencao: document.getElementById('com-cfg-retencao'),
        cfgRedirect: document.getElementById('com-cfg-redirect'),
        cfgAmbiente: document.getElementById('com-cfg-ambiente'),
        btnSalvar: document.getElementById('com-btn-salvar'),
        formTeste: document.getElementById('com-form-teste'),
        testeDestino: document.getElementById('com-teste-destino'),
        btnTestar: document.getElementById('com-btn-testar'),
        modal: document.getElementById('com-modal'),
        modalTitulo: document.getElementById('com-modal-titulo'),
        modalFechar: document.getElementById('com-modal-fechar'),
        detDestinatario: document.getElementById('com-det-destinatario'),
        detOriginal: document.getElementById('com-det-original'),
        detRemetente: document.getElementById('com-det-remetente'),
        detStatus: document.getElementById('com-det-status'),
        detTentativas: document.getElementById('com-det-tentativas'),
        detDatas: document.getElementById('com-det-datas'),
        detAnexos: document.getElementById('com-det-anexos'),
        detErro: document.getElementById('com-det-erro'),
        detPreview: document.getElementById('com-det-preview'),
        detCancelar: document.getElementById('com-det-cancelar'),
        detReenviar: document.getElementById('com-det-reenviar')
    };

    // ===== Utilidades

    function lerJson(valor, padrao) {
        if (!valor) return padrao;
        try {
            return JSON.parse(valor);
        } catch (e) {
            return padrao;
        }
    }

    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto === null || texto === undefined ? '' : String(texto);
        return div.innerHTML;
    }

    function nada(texto) {
        return texto ? texto : rotulos.labelNone;
    }

    function rotuloStatus(status) {
        var mapa = {
            pendente: rotulos.labelStatusPendente,
            enviando: rotulos.labelStatusEnviando,
            enviado: rotulos.labelStatusEnviado,
            falhou: rotulos.labelStatusFalhou,
            cancelado: rotulos.labelStatusCancelado
        };
        return mapa[status] || status;
    }

    function classeStatus(status) {
        if (status === 'enviado') return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
        if (status === 'falhou') return 'bg-rose-50 text-rose-700 ring-rose-600/20';
        if (status === 'pendente') return 'bg-sky-50 text-sky-800 ring-sky-600/20';
        if (status === 'enviando') return 'bg-amber-50 text-amber-800 ring-amber-600/20';
        return 'bg-slate-100 text-slate-600 ring-slate-500/20';
    }

    function rotuloCanal(id) {
        for (var i = 0; i < estado.canais.length; i++) {
            if (estado.canais[i].id === id) return estado.canais[i].rotulo;
        }
        return id;
    }

    function formatarData(valor) {
        return valor ? String(valor).replace('T', ' ').slice(0, 16) : '';
    }

    function texto(modelo, valores) {
        var saida = String(modelo || '');
        Object.keys(valores).forEach(function (chave) {
            saida = saida.split('#' + chave + '#').join(valores[chave]);
        });
        return saida;
    }

    function mostrarMensagem(conteudo, erro) {
        if (!el.mensagem) return;
        el.mensagem.textContent = conteudo;
        el.mensagem.className = erro
            ? 'mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800'
            : 'mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800';
    }

    function limparMensagem() {
        if (!el.mensagem) return;
        el.mensagem.textContent = '';
        el.mensagem.className = 'mb-4 hidden rounded-lg border px-4 py-3 text-sm';
    }

    // ===== Transporte (contrato AJAX do Gestor: `ajax=sim`; o global.js injeta o X-CSRF-Token)

    function chamar(ajaxOpcao, dados) {
        var params = new URLSearchParams({ opcao: 'painel', ajax: 'sim', ajaxOpcao: ajaxOpcao });
        if (dados) {
            Object.keys(dados).forEach(function (chave) {
                params.set(chave, dados[chave] === null || dados[chave] === undefined ? '' : dados[chave]);
            });
        }

        return fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
        }).then(function (resposta) {
            if (resposta.status === 401) {
                window.location.href = (window.gestor && window.gestor.raiz ? window.gestor.raiz : '/') + 'signin/';
                throw new Error('unauthorized');
            }
            if (!resposta.ok) throw new Error('HTTP ' + resposta.status);
            return resposta.json();
        }).then(function (json) {
            if (!json || json.status !== 'Ok') {
                throw new Error((json && json.message) || rotulos.msgGenericError);
            }
            return json;
        });
    }

    function aplicarEstatisticas(stats) {
        if (!stats) return;
        el.statEnviados.textContent = stats.enviados_hoje;
        el.statFila.textContent = stats.na_fila;
        el.statFalhas.textContent = stats.falhas;
        el.statLimite.textContent = stats.usados_minuto + ' / ' + stats.limite_minuto;
    }

    function aplicarBanner(textoBanner, config) {
        if (!el.banner) return;
        var base = 'mb-4 rounded-lg border-2 px-4 py-3 text-sm font-medium ';
        if (config.redirect_ativo) {
            el.banner.className = base + 'border-amber-300 bg-amber-50 text-amber-900';
        } else if (config.redirect_bloqueado) {
            el.banner.className = base + 'border-rose-300 bg-rose-50 text-rose-900';
        } else {
            el.banner.className = base + 'hidden';
        }
        el.bannerTexto.textContent = textoBanner || '';
    }

    // ===== Listagem

    function filtros() {
        return {
            status: el.status.value,
            canal: el.canal.value,
            busca: el.busca.value.trim(),
            data_de: el.de.value,
            data_ate: el.ate.value,
            pagina: estado.pagina
        };
    }

    function carregar() {
        if (!estado.disponivel) {
            renderizar();
            return Promise.resolve();
        }
        // `listar` é opção reservada do núcleo (interface_ajax_finalizar).
        return chamar('mensagens', filtros()).then(function (json) {
            estado.itens = json.data.itens || [];
            estado.pagina = json.data.pagina || 1;
            estado.paginas = json.data.paginas || 1;
            estado.total = json.data.total || 0;
            aplicarEstatisticas(json.data.estatisticas);
            renderizar();
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        });
    }

    function botaoAcao(acao, id, rotulo, primario) {
        return '<button type="button" class="c2fc-botao c2fc-botao-pequeno' + (primario ? ' c2fc-botao-primario' : '') + '"'
            + ' data-acao="' + acao + '" data-id="' + id + '">' + escapar(rotulo) + '</button>';
    }

    function renderizar() {
        if (!estado.itens.length) {
            el.corpo.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">' + escapar(rotulos.msgEmpty) + '</td></tr>';
        } else {
            el.corpo.innerHTML = estado.itens.map(function (item) {
                var destino = '<div class="break-all text-slate-900">' + escapar(item.destinatario) + '</div>';
                if (item.destinatario_original) {
                    destino += '<div class="break-all text-xs text-amber-700">' + escapar(texto(rotulos.msgRedirectedFrom, { original: item.destinatario_original })) + '</div>';
                }

                var status = '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' + classeStatus(item.status) + '">' + escapar(rotuloStatus(item.status)) + '</span>'
                    + '<div class="mt-1 text-xs text-slate-500">' + escapar(texto(rotulos.msgAttempts, { n: item.tentativas, max: item.max_tentativas })) + '</div>';
                if (item.status === 'pendente' && item.proxima_tentativa_em) {
                    status += '<div class="text-xs text-slate-500">' + escapar(texto(rotulos.msgNextTry, { data: formatarData(item.proxima_tentativa_em) })) + '</div>';
                }
                if (item.erro_ultimo && item.status !== 'enviado') {
                    status += '<div class="mt-1 max-w-xs truncate text-xs text-rose-700" title="' + escapar(item.erro_ultimo) + '">' + escapar(item.erro_ultimo) + '</div>';
                }

                var acoes = botaoAcao('ver', item.id, rotulos.labelView, false);
                if (item.status === 'falhou' || item.status === 'enviado') acoes += ' ' + botaoAcao('reenviar', item.id, rotulos.labelResend, true);
                if (item.status === 'pendente') acoes += ' ' + botaoAcao('cancelar', item.id, rotulos.labelCancel, false);

                return '<tr class="align-top hover:bg-slate-50">'
                    + '<td class="px-4 py-3 whitespace-nowrap text-slate-600">' + escapar(formatarData(item.data_criacao)) + '</td>'
                    + '<td class="px-4 py-3 whitespace-nowrap text-slate-600">' + escapar(rotuloCanal(item.canal)) + '</td>'
                    + '<td class="px-4 py-3">' + destino + '</td>'
                    + '<td class="px-4 py-3 text-slate-900">' + escapar(item.assunto) + '</td>'
                    + '<td class="px-4 py-3">' + status + '</td>'
                    + '<td class="px-4 py-3 text-right whitespace-nowrap">' + acoes + '</td>'
                    + '</tr>';
            }).join('');
        }

        el.info.textContent = texto(rotulos.msgPage, { pagina: estado.pagina, paginas: estado.paginas, total: estado.total });
        el.anterior.disabled = estado.pagina <= 1;
        el.proxima.disabled = estado.pagina >= estado.paginas;
    }

    // ===== Ações

    function cancelar(id) {
        if (!window.confirm(rotulos.msgConfirmCancel)) return;
        chamar('cancelar', { id: id }).then(function (json) {
            mostrarMensagem(json.message, false);
            fecharModal();
            return carregar();
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        });
    }

    function reenviar(id) {
        if (!window.confirm(rotulos.msgConfirmResend)) return;
        chamar('reenviar', { id: id }).then(function (json) {
            mostrarMensagem(json.message, false);
            fecharModal();
            return carregar();
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        });
    }

    function abrirModal(id) {
        chamar('detalhe', { id: id }).then(function (json) {
            var d = json.data;
            estado.aberto = d;

            el.modalTitulo.textContent = d.assunto || rotulos.labelNone;
            el.detDestinatario.textContent = nada(d.destinatario);
            el.detOriginal.textContent = nada(d.destinatario_original);
            el.detRemetente.textContent = nada(d.remetente);
            el.detStatus.textContent = rotuloStatus(d.status) + ' · ' + rotuloCanal(d.canal);
            el.detTentativas.textContent = texto(rotulos.msgAttempts, { n: d.tentativas, max: d.max_tentativas });
            el.detDatas.textContent = formatarData(d.data_criacao) + (d.enviado_em ? ' → ' + formatarData(d.enviado_em) : '');
            el.detAnexos.textContent = d.anexos && d.anexos.length ? d.anexos.join(', ') : rotulos.labelNone;
            el.detErro.textContent = nada(d.erro_ultimo);
            el.detPreview.srcdoc = d.html || '';

            el.detCancelar.classList.toggle('hidden', d.status !== 'pendente');
            el.detReenviar.classList.toggle('hidden', d.status !== 'falhou' && d.status !== 'enviado');

            el.modal.classList.remove('hidden');
            el.modal.classList.add('flex');
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        });
    }

    function fecharModal() {
        el.modal.classList.add('hidden');
        el.modal.classList.remove('flex');
        el.detPreview.srcdoc = '';
        estado.aberto = null;
    }

    // ===== Configurações

    function preencherConfig(config) {
        estado.config = config;
        el.cfgModo.value = config.modo || 'hibrido';
        el.cfgRate.value = config.rate_limit;
        el.cfgTentativas.value = config.max_tentativas;
        el.cfgRetencao.value = config.retencao_dias;
        el.cfgRedirect.value = config.mail_redirect_to || '';
        el.cfgAmbiente.textContent = config.ambiente || '';
    }

    function salvarConfig(evento) {
        evento.preventDefault();
        el.btnSalvar.disabled = true;
        chamar('salvar', {
            modo: el.cfgModo.value,
            rate_limit: el.cfgRate.value,
            max_tentativas: el.cfgTentativas.value,
            retencao_dias: el.cfgRetencao.value,
            mail_redirect_to: el.cfgRedirect.value.trim()
        }).then(function (json) {
            preencherConfig(json.data.config);
            aplicarBanner(json.data.banner, json.data.config);
            aplicarEstatisticas(json.data.estatisticas);
            mostrarMensagem(json.message, false);
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        }).then(function () {
            el.btnSalvar.disabled = false;
        });
    }

    function enviarTeste(evento) {
        evento.preventDefault();
        el.btnTestar.disabled = true;
        chamar('testar', { destino: el.testeDestino.value.trim() }).then(function (json) {
            mostrarMensagem(json.message, false);
            aplicarEstatisticas(json.data.estatisticas);
            return carregar();
        }).catch(function (erro) {
            mostrarMensagem(erro.message, true);
        }).then(function () {
            el.btnTestar.disabled = false;
        });
    }

    // ===== Abas

    function trocarAba(aba) {
        painel.querySelectorAll('[data-aba]').forEach(function (botao) {
            var ativa = botao.dataset.aba === aba;
            botao.setAttribute('aria-selected', ativa ? 'true' : 'false');
            botao.className = '-mb-px border-b-2 px-4 py-2 text-sm '
                + (ativa ? 'border-sky-700 font-semibold text-sky-800' : 'border-transparent font-medium text-slate-600 hover:text-slate-900');
        });
        painel.querySelectorAll('[data-painel]').forEach(function (bloco) {
            bloco.classList.toggle('hidden', bloco.dataset.painel !== aba);
        });
    }

    // ===== Eventos

    function filtrar() {
        estado.pagina = 1;
        limparMensagem();
        carregar();
    }

    estado.canais.forEach(function (canal) {
        var opcao = document.createElement('option');
        opcao.value = canal.id;
        opcao.textContent = canal.rotulo;
        el.canal.appendChild(opcao);
    });

    [el.status, el.canal, el.de, el.ate].forEach(function (campo) {
        campo.addEventListener('change', filtrar);
    });

    el.busca.addEventListener('input', function () {
        clearTimeout(estado.buscaTimer);
        estado.buscaTimer = setTimeout(filtrar, 350);
    });

    el.anterior.addEventListener('click', function () {
        if (estado.pagina > 1) { estado.pagina--; carregar(); }
    });
    el.proxima.addEventListener('click', function () {
        if (estado.pagina < estado.paginas) { estado.pagina++; carregar(); }
    });
    el.atualizar.addEventListener('click', function () { limparMensagem(); carregar(); });

    el.corpo.addEventListener('click', function (evento) {
        var botao = evento.target.closest('button[data-acao]');
        if (!botao) return;
        var id = botao.dataset.id;
        if (botao.dataset.acao === 'ver') abrirModal(id);
        else if (botao.dataset.acao === 'reenviar') reenviar(id);
        else if (botao.dataset.acao === 'cancelar') cancelar(id);
    });

    el.modalFechar.addEventListener('click', fecharModal);
    el.modal.addEventListener('click', function (evento) {
        if (evento.target === el.modal) fecharModal();
    });
    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && !el.modal.classList.contains('hidden')) fecharModal();
    });
    el.detCancelar.addEventListener('click', function () { if (estado.aberto) cancelar(estado.aberto.id); });
    el.detReenviar.addEventListener('click', function () { if (estado.aberto) reenviar(estado.aberto.id); });

    painel.querySelectorAll('[data-aba]').forEach(function (botao) {
        botao.addEventListener('click', function () { trocarAba(botao.dataset.aba); });
    });

    el.formConfig.addEventListener('submit', salvarConfig);
    el.formTeste.addEventListener('submit', enviarTeste);

    preencherConfig(estado.config);
    carregar();
})();

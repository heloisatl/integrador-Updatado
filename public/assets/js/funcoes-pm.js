(() => {
    'use strict';

    let navegacaoAtiva = true;
    let imagemSelecionada = '';
    let arquivoImagemSelecionado = null;
    let larguraPrevia = 1200;
    let alteracoesPendentes = false;

    const buscar = (id) => document.getElementById(id);
    const ler = (id, padrao = '') => buscar(id)?.value.trim() || padrao;
    const marcado = (id) => buscar(id)?.checked === true;

    function criarCampoLink() {
        const grupo = document.createElement('div');
        grupo.className = 'pm-grupo-link';
        grupo.innerHTML = `
            <input type="text" name="links" placeholder="Nome do link">
            <input type="url" name="href" placeholder="https://exemplo.com">
            <button type="button" class="pm-botao pm-botao-pequeno">-</button>`;
        grupo.querySelector('button').addEventListener('click', () => grupo.remove());
        return grupo;
    }

    function adicionarLink() {
        const lista = document.querySelector('.pm-links-list');
        const botao = buscar('adicionarLink');
        if (lista && botao) lista.insertBefore(criarCampoLink(), botao);
    }

    function atualizarNavegacao() {
        const lista = document.querySelector('.pm-links-list');
        const controle = buscar('semNavegacao');
        if (!lista || !controle) return;
        navegacaoAtiva = !controle.checked;
        lista.classList.toggle('pm-oculto', !navegacaoAtiva);
    }

    function obterNomeArquivoImagem(arquivo) {
        const extensoes = {
            'image/jpeg': 'jpg',
            'image/png': 'png',
            'image/gif': 'gif',
            'image/webp': 'webp'
        };
        const nomeBase = ler('nomeImagem') || arquivo.name.replace(/\.[^.]+$/, '');
        const nomeSeguro = nomeBase.replace(/[^A-Za-z0-9_-]/g, '') || 'imagem';
        return `${nomeSeguro}.${extensoes[arquivo.type]}`;
    }

    function obterImagem(paraDownload = false) {
        if (arquivoImagemSelecionado) {
            return paraDownload
                ? obterNomeArquivoImagem(arquivoImagemSelecionado)
                : imagemSelecionada;
        }

        const url = ler('urlImagem');
        if (/^www\./i.test(url)) return `https://${url}`;
        return url;
    }

    function atualizarTamanhoFonteCustomizado() {
        const campo = buscar('campoTamanhoFonteCustom');
        if (campo) campo.hidden = ler('tamanhoFonte') !== 'outro';
    }

    function converterCor(cor, opacidade) {
        const valores = cor.replace('#', '').match(/.{2}/g).map((valor) => parseInt(valor, 16));
        return `rgba(${valores.join(', ')}, ${opacidade})`;
    }

    function gerarCss(comComentarios = false) {
        const comentario = (texto) => comComentarios ? `/* ${texto} */\n` : '';
        const tamanhos = { small: '14px', medium: '18px', large: '24px', outro: '18px' };
        const tamanhoSelecionado = ler('tamanhoFonte', 'medium');
        const tamanhoCustom = Number(ler('tamanhoFonteCustom', '18'));
        const tamanho = tamanhoSelecionado === 'outro'
            ? `${Math.min(72, Math.max(8, Number.isFinite(tamanhoCustom) ? tamanhoCustom : 18))}px`
            : (tamanhos[tamanhoSelecionado] || tamanhos.medium);
        const largura = ler('larguraImagem', 'auto');
        const sombra = { none: 'none', leve: '0 4px 14px rgba(0,0,0,.15)', forte: '0 10px 28px rgba(0,0,0,.3)' }[ler('tipoSombra', 'none')];
        const opacidade = Number(ler('opacidadeFundo', '100')) / 100;
        const fundoInicial = converterCor(ler('corGradienteInicial', '#ffffff'), opacidade);
        const fundoFinal = converterCor(ler('corGradienteFinal', '#e8e8ff'), opacidade);
        const imagemFundo = ler('imagemFundo');
        const fundo = imagemFundo
            ? `linear-gradient(${fundoInicial}, ${fundoFinal}), url('${imagemFundo}')`
            : `linear-gradient(${fundoInicial}, ${fundoFinal})`;
        const estilo = document.querySelector('input[name="estiloLinks"]:checked')?.value === '0' ? 'none' : 'underline';
        const urlFonte = ler('urlFonte');
        let familiaFonte = 'Arial';
        if (urlFonte) {
            try {
                const familia = new URL(urlFonte).searchParams.get('family') || '';
                const nomeFamilia = decodeURIComponent(familia.split(':')[0].replace(/\+/g, ' '));
                if (/^[A-Za-z0-9 -]+$/.test(nomeFamilia)) familiaFonte = `'${nomeFamilia}'`;
            } catch (erro) {
                familiaFonte = 'Arial';
            }
        }

        return `${urlFonte ? `@import url('${urlFonte}');\n\n` : ''}${comentario('Variáveis de cores reutilizadas pelas regras abaixo. Duplique uma variável para criar outra cor reutilizável.')}:root {
    --cor-fundo: ${ler('corFundo', '#ffffff')};
    --cor-fonte: ${ler('corFonte', '#000000')};
    --cor-links: ${ler('corLinks', '#0000ff')};
    --cor-botao: ${ler('corBotao', '#5b6af0')};
    --cor-botao-hover: ${ler('corBotaoHover', '#4a59df')};
}

${comentario('Estilo geral da página: fundo, fonte, espaçamento e texto. Duplique a regra para criar um estilo para outra área.')}body {
    background-color: var(--cor-fundo);
    background-image: ${fundo};
    background-size: cover;
    color: var(--cor-fonte);
    font-family: ${familiaFonte}, sans-serif;
    font-size: ${tamanho};
    font-weight: ${ler('pesoFonte', '400')};
    line-height: ${ler('alturaLinha', '1.5')};
    margin: ${ler('margemPagina', '24')}px;
    padding: ${ler('preenchimentoPagina', '24')}px;
}

${comentario('Cabeçalho da página. Duplique o elemento <header> no HTML para adicionar outro cabeçalho.')}header {
    border-radius: ${ler('raioBorda', '8')}px;
    box-shadow: ${sombra};
    padding: ${ler('preenchimentoPagina', '24')}px;
    ${marcado('cabecalhoFixo') ? 'position: sticky; top: 0; z-index: 2;' : ''}
}

${comentario('Organiza os elementos principais em colunas e controla alinhamento e espaçamento.')}.grade-conteudo {
    align-items: ${ler('alinhamentoVertical', 'flex-start')};
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: ${ler('preenchimentoPagina', '24')}px;
    justify-content: ${ler('alinhamentoHorizontal', 'flex-start')};
}

${comentario('Define a largura dos elementos filhos da área principal. Duplique elementos no HTML para preencher as colunas.')}.grade-conteudo > * {
    flex: 1 1 calc(${100 / Number(ler('tipoLayout', '1'))}% - 24px);
    min-width: 0;
}

${comentario('Aparência dos links de navegação. Duplique um <a> dentro de <nav> no HTML para criar outro link.')}nav a {
    color: var(--cor-links);
    margin-right: 16px;
    text-decoration: ${estilo};
}

${comentario('Estilo compartilhado de borda e sombra para imagens, cartões, tabelas e botões.')}img, .cartao-conteudo, table, .botao-cta {
    border-radius: ${ler('raioBorda', '8')}px;
    box-shadow: ${sombra};
}

${comentario('Limita a imagem à largura disponível. Duplique o bloco de imagem no HTML para adicionar outra.')}img { max-width: 100%; width: ${largura === 'auto' ? 'auto' : largura + 'px'}; }
${comentario('Aparência dos botões de destaque. Duplique o link com a classe botao-cta no HTML para criar outro botão.') }
.botao-cta { background: var(--cor-botao); color: #fff; display: inline-block; padding: 10px 16px; text-decoration: none; }
.botao-cta:hover { background: var(--cor-botao-hover); }
${comentario('Organiza os campos do formulário. Duplique os campos no HTML para acrescentar perguntas.') }
.formulario-contato { display: grid; gap: 12px; max-width: 520px; }
.formulario-contato input, .formulario-contato textarea { border: 1px solid var(--cor-links); border-radius: ${ler('raioBorda', '8')}px; padding: 10px; }

${comentario('Adapta o conteúdo para telas estreitas, como celulares. Ajuste o limite de largura se necessário.') }
@media (max-width: ${larguraPrevia}px) {
    .grade-conteudo { flex-direction: column; }
    .grade-conteudo > * { flex-basis: auto; width: 100%; }
}`;
    }

    function gerarLinks() {
        if (!navegacaoAtiva) return '';
        return [...document.querySelectorAll('input[name="links"]')].map((link) => {
            const url = link.closest('.pm-grupo-link')?.querySelector('input[name="href"]')?.value.trim() || '';
            return link.value.trim() && url ? `<a href="${url}">${link.value.trim()}</a>` : '';
        }).filter(Boolean).join('\n');
    }

    function gerarComponentes(paraDownload = false) {
        const comentario = (texto) => paraDownload ? `<!-- ${texto} -->\n` : '';
        const imagem = obterImagem(paraDownload);
        const textoAlternativo = ler('altTextImagem') || ler('altImagem') || ler('nomeImagem', 'Imagem do projeto');
        const imagemHtml = imagem ? `<img src="${imagem}" alt="${textoAlternativo}">` : '';
        const card = marcado('incluirCard') ? `${comentario('Cartão de conteúdo. Duplique este <article> para adicionar outro cartão e altere seu título, texto e link.')}<article class="cartao-conteudo">${imagemHtml}<h2>${ler('textoCabecalho', 'Meu Projeto')}</h2><p>${ler('textoPagina', 'Conteúdo do card')}</p><a class="botao-cta" href="${ler('linkBotao', '#')}">${ler('textoBotao', 'Saiba mais')}</a></article>` : '';
        const formulario = marcado('incluirFormulario') ? `${comentario('Formulário de exemplo. Duplique os campos para incluir perguntas; conecte-o a um serviço para receber os envios.')}<form class="formulario-contato"><input placeholder="Nome" required><input type="email" placeholder="Email" required><textarea placeholder="Mensagem" required></textarea><button class="botao-cta" type="submit">Enviar</button></form>` : '';
        const lista = marcado('incluirLista') ? `${comentario('Lista de itens. Duplique o elemento <li> para acrescentar itens.')}<ul><li>Item da lista</li></ul>` : '';
        const tabela = marcado('incluirTabela') ? `${comentario('Tabela simples. Duplique a linha <tr> e suas células <td> para adicionar dados.')}<table border="1"><tr><td>Dados</td></tr></table>` : '';
        const rodape = marcado('incluirRodape') ? `${comentario('Rodapé da página. Duplique ou edite este <footer> para incluir outras informações ao final.')}<footer>Gerado pelo DevStudio</footer>` : '';
        const ordem = ler('ordemElementos', 'texto-imagem-lista-tabela').split('-');
        const elementos = {
            texto: `${comentario('Bloco de texto. Duplique este <p> para adicionar outro parágrafo.')}<p class="bloco-texto">${ler('textoPagina', 'Conteúdo da página')}</p>`,
            imagem: imagemHtml ? `${comentario('Bloco de imagem. Duplique esta <div> e altere o src e o alt da imagem para adicionar outra.')}<div class="bloco-imagem">${imagemHtml}</div>` : '',
            lista,
            tabela
        };
        const conteudo = ordem.map((item) => elementos[item] || '').join('\n');
        return { conteudo: `${conteudo}${card}${formulario}`, rodape };
    }

    function gerarHtml(paraDownload = false) {
        const comentario = (texto) => paraDownload ? `    <!-- ${texto} -->\n` : '';
        const links = gerarLinks();
        const componentes = gerarComponentes(paraDownload);
        return `<!doctype html>
<html lang="pt-BR">
${comentario('Página gerada pelo DevStudio. Edite o conteúdo abaixo conforme necessário.')}
<head>
    ${comentario('Metadados e título da aba. Edite o <title> para mudar o nome mostrado pelo navegador.')}
    <meta charset="utf-8">
    <title>${ler('tituloAba', 'Meu Projeto')}</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
${comentario('Cabeçalho da página. Duplique o elemento <header> para acrescentar outro cabeçalho.')}
    <header><h1>${ler('textoCabecalho', 'Meu Projeto')}</h1></header>
    ${links ? `${comentario('Navegação. Duplique um elemento <a> dentro de <nav> para adicionar outro link.')}<nav>${links}</nav>` : ''}
${comentario('Conteúdo principal. Os blocos abaixo podem ser duplicados para acrescentar elementos.')}
    <main class="grade-conteudo">${componentes.conteudo}</main>
${comentario('Botão de destaque. Duplique este link para criar outro botão e altere o texto e o href.')}
    <a class="botao-cta" href="${ler('linkBotao', '#')}">${ler('textoBotao', 'Saiba mais')}</a>
    ${componentes.rodape}
</body>
</html>`;
    }

    function atualizarSaida() {
        buscar('codeCSS').value = gerarCss(true);
        buscar('codeHTML').value = gerarHtml(true);
        renderizarPrevia();
    }

    function renderizarPrevia() {
        const iframe = buscar('pagina');
        if (!iframe) return;
        iframe.style.width = `${larguraPrevia}px`;
        const documento = iframe.contentDocument || iframe.contentWindow.document;
        const css = buscar('codeCSS').value;
        const html = buscar('codeHTML').value.replace(
            '<link rel="stylesheet" href="estilo.css">',
            `<style>${css}</style>`
        );
        documento.open();
        documento.write(html);
        documento.close();
        documento.querySelectorAll('img').forEach((imagem) => {
            imagem.addEventListener('error', () => {
                buscar('pmHistoricoStatus').textContent = 'Não foi possível carregar a imagem. Verifique se o endereço é direto e acessível.';
            });
        });
    }

    async function baixarZip() {
        atualizarSaida();
        const raiz = buscar('pageMaker');
        const dados = new FormData();
        dados.append('nomeArquivo', ler('nomeArquivo', 'pagina'));
        dados.append('html', buscar('codeHTML').value);
        dados.append('css', buscar('codeCSS').value);
        if (arquivoImagemSelecionado) {
            dados.append('imagem', arquivoImagemSelecionado, arquivoImagemSelecionado.name);
            dados.append('nomeImagemArquivo', obterNomeArquivoImagem(arquivoImagemSelecionado));
        }

        const resposta = await fetch(`${raiz.dataset.urlBase}/projetos/pagemaker/baixarZip`, {
            method: 'POST',
            body: dados
        });
        if (!resposta.ok) {
            const erro = await resposta.json();
            throw new Error(erro.mensagem || 'Não foi possível criar o arquivo ZIP.');
        }

        const arquivo = await resposta.blob();
        const url = URL.createObjectURL(arquivo);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${ler('nomeArquivo', 'pagina').replace(/[^A-Za-z0-9_-]/g, '') || 'pagina'}.zip`;
        link.click();
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
        alteracoesPendentes = false;
        const statusEl = buscar('pmHistoricoStatus');
        if (statusEl) {
            statusEl.textContent = arquivoImagemSelecionado
                ? 'ZIP baixado e projeto salvo no Histórico com sucesso (HTML, CSS e imagem)!'
                : 'ZIP baixado e projeto salvo no Histórico com sucesso!';
            statusEl.style.color = '#4ade80';
        }
    }

    async function salvarNoHistorico() {
        atualizarSaida();
        const raiz = buscar('pageMaker');
        const nomeArquivo = ler('nomeArquivo', 'pagina').replace(/[^A-Za-z0-9_-]/g, '') || 'pagina';
        const html = buscar('codeHTML').value;
        const css = buscar('codeCSS').value;
        const statusEl = buscar('pmHistoricoStatus');

        if (!html || !css) {
            if (statusEl) {
                statusEl.textContent = 'Gere a prévia antes de salvar no Histórico.';
                statusEl.style.color = '#f59e0b';
            }
            return;
        }

        if (statusEl) {
            statusEl.textContent = 'Salvando projeto no Histórico...';
            statusEl.style.color = 'var(--muted)';
        }

        const dados = new FormData();
        dados.append('nomeArquivo', nomeArquivo);
        dados.append('html', html);
        dados.append('css', css);

        try {
            const resp = await fetch(`${raiz.dataset.urlBase}/projetos/pagemaker/salvar-historico`, {
                method: 'POST',
                body: dados
            });
            const res = await resp.json();
            if (res.sucesso) {
                alteracoesPendentes = false;
                if (statusEl) {
                    statusEl.textContent = `Página "${nomeArquivo}" salva no Histórico com sucesso!`;
                    statusEl.style.color = '#4ade80';
                }
            } else {
                if (statusEl) {
                    statusEl.textContent = res.mensagem || 'Não foi possível salvar no Histórico.';
                    statusEl.style.color = '#ef4444';
                }
            }
        } catch (err) {
            if (statusEl) {
                statusEl.textContent = 'Erro de comunicação ao salvar no Histórico.';
                statusEl.style.color = '#ef4444';
            }
        }
    }

    function atualizarValores() {
        const saidas = {
            margemPagina: ['valorMargem', 'px'],
            preenchimentoPagina: ['valorPreenchimento', 'px'],
            alturaLinha: ['valorAlturaLinha', ''],
            raioBorda: ['valorRaioBorda', 'px'],
            opacidadeFundo: ['valorOpacidade', '%']
        };
        document.querySelectorAll('input[type="range"]').forEach((controle) => {
            const configuracao = saidas[controle.id];
            const saida = configuracao ? buscar(configuracao[0]) : null;
            if (saida) saida.textContent = controle.value + configuracao[1];
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        buscar('adicionarLink')?.addEventListener('click', adicionarLink);
        buscar('semNavegacao')?.addEventListener('change', () => {
            atualizarNavegacao();
            atualizarSaida();
        });
        buscar('visualizarPrevia')?.addEventListener('click', atualizarSaida);
        buscar('salvarHistorico')?.addEventListener('click', () => {
            salvarNoHistorico().catch((erro) => {
                const status = buscar('pmHistoricoStatus');
                if (status) {
                    status.textContent = erro.message;
                    status.style.color = '#ef4444';
                }
            });
        });
        buscar('baixarZip')?.addEventListener('click', () => {
            baixarZip().catch((erro) => {
                const status = buscar('pmHistoricoStatus');
                if (status) status.textContent = erro.message;
            });
        });
        document.querySelectorAll('input[type="range"]').forEach((controle) => controle.addEventListener('input', atualizarValores));
        document.querySelectorAll('[data-largura]').forEach((botao) => botao.addEventListener('click', () => {
            larguraPrevia = Number.parseInt(botao.dataset.largura, 10);
            document.querySelectorAll('[data-largura]').forEach((item) => item.classList.remove('pm-tela-ativa'));
            botao.classList.add('pm-tela-ativa');
            atualizarSaida();
        }));
        buscar('pageMaker')?.addEventListener('input', () => {
            alteracoesPendentes = true;
            atualizarSaida();
        });
        buscar('pageMaker')?.addEventListener('change', () => { alteracoesPendentes = true; });
        buscar('pageMaker')?.addEventListener('click', (evento) => {
            if (evento.target.closest('#adicionarLink, .pm-botao-pequeno, [data-largura]')) {
                alteracoesPendentes = true;
                atualizarSaida();
            }
        });
        // Alerta flutuante no topo seguindo os alerts do sistema (DevStudio Identity)
        function devstudioConfirmarSaida(urlDestino) {
            let container = document.getElementById('devstudioToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'devstudioToastContainer';
                container.className = 'devstudio-toast-container';
                document.body.appendChild(container);
            }

            // Remove alertas de confirmação anteriores se existirem
            container.querySelectorAll('.devstudio-toast-confirmacao').forEach(el => el.remove());

            const toast = document.createElement('div');
            toast.className = 'devstudio-toast devstudio-toast-aviso devstudio-toast-confirmacao';
            toast.style.pointerEvents = 'auto';

            toast.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill devstudio-toast-icon" style="color: #f59e0b;" aria-hidden="true"></i>
                <div class="devstudio-toast-content">
                    <div class="devstudio-toast-title">Alterações Não Salvas</div>
                    <span>Você realizou modificações no PageMaker. Se sair agora, as alterações não salvas serão perdidas.</span>
                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                        <button type="button" class="btn btn-secondary btn-sm" id="pmBtnFicar" style="padding: 4px 10px; font-size: 12px; cursor: pointer;">Continuar Editando</button>
                        <button type="button" class="btn btn-sm" id="pmBtnSair" style="padding: 4px 10px; font-size: 12px; background: #f59e0b; color: #fff; border: none; cursor: pointer;">Descartar e Sair</button>
                    </div>
                </div>
                <button type="button" class="devstudio-toast-close" aria-label="Fechar">&times;</button>
            `;

            const fechar = () => {
                toast.classList.add('is-hiding');
                setTimeout(() => {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 260);
            };

            toast.querySelector('.devstudio-toast-close')?.addEventListener('click', fechar);
            toast.querySelector('#pmBtnFicar')?.addEventListener('click', fechar);
            toast.querySelector('#pmBtnSair')?.addEventListener('click', () => {
                alteracoesPendentes = false;
                fechar();
                if (urlDestino) {
                    window.location.href = urlDestino;
                }
            });

            container.appendChild(toast);
        }

        // Intercepta navegações via links
        document.addEventListener('click', (evento) => {
            const link = evento.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href') || '';

            // Se for âncora de seção do PageMaker (#secao-...), faz rolagem suave sem recarregar nem disparar alerta
            if (href.includes('#secao-')) {
                const secaoId = href.split('#')[1];
                const alvo = document.getElementById(secaoId);
                if (alvo) {
                    evento.preventDefault();
                    alvo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    document.querySelectorAll('#sb-titulo-PageMaker .sb-item').forEach(el => el.classList.remove('active'));
                    const itemSidebar = link.closest('.sb-item') || link;
                    itemSidebar.classList.add('active');
                    return;
                }
            }

            // Se não há alterações pendentes, permite navegação normal
            if (!alteracoesPendentes) return;

            // Ignora âncoras locais, javascript e nova aba
            if (href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') {
                return;
            }

            // Bloqueia e exibe alerta do sistema no topo
            evento.preventDefault();
            devstudioConfirmarSaida(link.href);
        });

        window.addEventListener('beforeunload', (evento) => {
            if (!alteracoesPendentes) return;
            evento.preventDefault();
            evento.returnValue = '';
        });
        buscar('fileImagem')?.addEventListener('change', (evento) => {
            const arquivo = evento.target.files[0];
            const tiposAceitos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!arquivo) {
                if (imagemSelecionada.startsWith('blob:')) URL.revokeObjectURL(imagemSelecionada);
                arquivoImagemSelecionado = null;
                imagemSelecionada = '';
                atualizarSaida();
                return;
            }
            if (!tiposAceitos.includes(arquivo.type) || arquivo.size > 10 * 1024 * 1024) {
                evento.target.value = '';
                if (imagemSelecionada.startsWith('blob:')) URL.revokeObjectURL(imagemSelecionada);
                arquivoImagemSelecionado = null;
                imagemSelecionada = '';
                buscar('pmHistoricoStatus').textContent = 'Selecione uma imagem JPG, PNG, GIF ou WebP de até 10 MB.';
                atualizarSaida();
                return;
            }
            if (imagemSelecionada.startsWith('blob:')) URL.revokeObjectURL(imagemSelecionada);
            arquivoImagemSelecionado = arquivo;
            buscar('urlImagem').value = '';
            imagemSelecionada = URL.createObjectURL(arquivo);
            buscar('pmHistoricoStatus').textContent = '';
            atualizarSaida();
        });
        buscar('urlImagem')?.addEventListener('input', () => {
            if (ler('urlImagem') && arquivoImagemSelecionado) {
                if (imagemSelecionada.startsWith('blob:')) URL.revokeObjectURL(imagemSelecionada);
                arquivoImagemSelecionado = null;
                imagemSelecionada = '';
                buscar('fileImagem').value = '';
                atualizarSaida();
            }
            buscar('pmHistoricoStatus').textContent = '';
        });
        buscar('tamanhoFonte')?.addEventListener('change', atualizarTamanhoFonteCustomizado);
        atualizarNavegacao();
        atualizarTamanhoFonteCustomizado();
        atualizarValores();
        adicionarLink();
    });
})();

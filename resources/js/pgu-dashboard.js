import html2canvas from 'html2canvas';
import PptxGenJS from 'pptxgenjs';
import { initAppLucideIcons } from './charts/icons.js';

window.pguDashboard = function () {
    return {
        loading: true,
        error: null,
        data: null,
        charts: {},
        contrato: '',
        competencia: '',
        dataLimite: '',
        visaoAba: 'cliente',
        clienteMenuOpen: false,
        clienteEvolucaoMenuOpen: false,
        clienteCoberturaMenuOpen: false,
        clienteMapaMenuOpen: false,
        clienteDestaquesMenuOpen: false,
        clientePlanoMenuOpen: false,
        clienteCicloMenuOpen: false,
        setVisaoAba(tab) {
            this.visaoAba = tab;
            this.clienteMenuOpen = false;
            this.clienteEvolucaoMenuOpen = false;
            this.clienteCoberturaMenuOpen = false;
            this.clienteMapaMenuOpen = false;
            this.clienteDestaquesMenuOpen = false;
            this.clientePlanoMenuOpen = false;
            this.clienteCicloMenuOpen = false;
            queueMicrotask(() => this.scheduleChartsResize());
            setTimeout(() => this.scheduleChartsResize(), 120);
        },
        toggleClienteMenu() {
            this.clienteMenuOpen = !this.clienteMenuOpen;
        },
        closeClienteMenu() {
            this.clienteMenuOpen = false;
        },
        toggleClienteEvolucaoMenu() {
            this.clienteEvolucaoMenuOpen = !this.clienteEvolucaoMenuOpen;
        },
        closeClienteEvolucaoMenu() {
            this.clienteEvolucaoMenuOpen = false;
        },
        toggleClienteCoberturaMenu() {
            this.clienteCoberturaMenuOpen = !this.clienteCoberturaMenuOpen;
        },
        closeClienteCoberturaMenu() {
            this.clienteCoberturaMenuOpen = false;
        },
        toggleClienteMapaMenu() {
            this.clienteMapaMenuOpen = !this.clienteMapaMenuOpen;
        },
        closeClienteMapaMenu() {
            this.clienteMapaMenuOpen = false;
        },
        toggleClienteCicloMenu() {
            this.clienteCicloMenuOpen = !this.clienteCicloMenuOpen;
        },
        closeClienteCicloMenu() {
            this.clienteCicloMenuOpen = false;
        },
        clienteInfo() {
            window.alert('Panorama Executivo do PGU (Visão Cliente): mostra a distribuição das vagas por fase do recrutamento e o percentual consolidado da fase final sobre o total de vagas monitoradas.');
        },
        exportClientePanorama() {
            this.exportChartPng('chartClientePanorama');
            this.closeClienteMenu();
        },
        clienteEvolucaoInfo() {
            window.alert('Evolução da Base Funcional: colunas mostram vagas consolidadas por competência e a linha mostra o % de cobertura monitorada no período.');
        },
        exportClienteEvolucao() {
            this.exportChartPng('chartClienteEvolucao');
            this.closeClienteEvolucaoMenu();
        },
        clienteCoberturaInfo() {
            window.alert('Cobertura Operacional Monitorada: mostra o acompanhamento por frente operacional com base nas fases do recrutamento e a cobertura relativa ao total de vagas mapeadas.');
        },
        exportClienteCobertura() {
            this.exportChartPng('chartClienteCobertura');
            this.closeClienteCoberturaMenu();
        },
        clienteMapaInfo() {
            window.alert('Mapa de Consolidação por Função: detalha por função o total mapeado, consolidado, em evolução e o índice de consolidação.');
        },
        exportClienteMapa() {
            this.exportChartPng('chartClienteMapaDonut');
            this.closeClienteMapaMenu();
        },
        toggleClienteDestaquesMenu() {
            this.clienteDestaquesMenuOpen = !this.clienteDestaquesMenuOpen;
        },
        closeClienteDestaquesMenu() {
            this.clienteDestaquesMenuOpen = false;
        },
        clienteDestaquesInfo() {
            window.alert(
                'Destaques operacionais do ciclo: resume avanços do período (do início do ciclo até a data atual), movimentação consolidada/pendências, '
                + 'funções com menor índice e gráficos de progresso e de participação das etapas de maturidade.',
            );
        },
        exportClienteDestaques() {
            this.exportChartPng('chartClienteDestaquesLinha');
            this.closeClienteDestaquesMenu();
        },
        toggleClientePlanoMenu() {
            this.clientePlanoMenuOpen = !this.clientePlanoMenuOpen;
        },
        closeClientePlanoMenu() {
            this.clientePlanoMenuOpen = false;
        },
        clientePlanoInfo() {
            window.alert(
                'Plano de acompanhamento até a data limite: consolida roteiro das etapas de maturidade, plano de ações sugerido e projeção de conclusão com base no progresso consolidado do ciclo.',
            );
        },
        exportClientePlano() {
            this.exportChartPng('chartClientePlanoSemiDonut');
            this.closeClientePlanoMenu();
        },
        clienteCicloInfo() {
            window.alert('Avanço do Ciclo até a Data Limite: mostra o progresso consolidado do ciclo de mobilização, marco de data limite e situação atual das vagas do contrato.');
        },
        exportClienteCiclo() {
            this.exportElementPng('cardClienteCiclo');
            this.closeClienteCicloMenu();
        },
        /** Slides da apresentação PGU (abas superiores). */
        abaApresentacao: 'capa',
        setAbaApresentacao(slug) {
            if (this.abaApresentacao === slug) {
                return;
            }
            this.abaApresentacao = slug;
            queueMicrotask(() => {
                this.scheduleChartsResize();
                setTimeout(() => this.scheduleChartsResize(), 200);
            });
        },
        rankingExecutivoTop(n) {
            const r = this.data?.ranking_executivo;
            const lim = Math.max(0, Number(n) || 5);
            if (!Array.isArray(r)) return [];
            return r.slice(0, lim);
        },
        paretoMetrics(items) {
            const list = Array.isArray(items) ? items : [];
            const totalPendencias = list.reduce((acc, row) => acc + Number(row?.pending || 0), 0);
            const top3Pendencias = list.slice(0, 3).reduce((acc, row) => acc + Number(row?.pending || 0), 0);
            const concentracaoTop3 = totalPendencias > 0 ? (top3Pendencias / totalPendencias) * 100 : 0;
            return {
                totalPendencias: Math.round(totalPendencias),
                top3Pendencias: Math.round(top3Pendencias),
                concentracaoTop3: Math.round(concentracaoTop3 * 10) / 10,
                itens: list.length,
            };
        },
        clientePanorama() {
            const summary = this.data?.summary || {};
            const mapeadas = Number(summary.total_functions || 0);
            // `completed_functions` na API = somente vagas em linhas com progresso 100% (subconjunto).
            // `vagas_concluidas` = soma de todas as vagas consolidadas — alinha com o mapa por função e KPIs de ciclo.
            const consolidadas = Number(
                summary.vagas_concluidas ?? summary.completed_functions ?? 0,
            );
            const emEvolucao = Math.max(0, mapeadas - consolidadas);
            const pctConsolidada = mapeadas > 0 ? (consolidadas / mapeadas) * 100 : 0;
            const pctEvolucao = Math.max(0, 100 - pctConsolidada);
            return {
                mapeadas,
                consolidadas,
                emEvolucao,
                monitoradas: mapeadas > 0 ? 100 : 0,
                pctConsolidada: Math.round(pctConsolidada * 10) / 10,
                pctEvolucao: Math.round(pctEvolucao * 10) / 10,
            };
        },
        clienteFases() {
            const fases = Array.isArray(this.data?.fase_atual) ? this.data.fase_atual : [];
            const colors = ['#6F1731', '#73203D', '#8B2C4A', '#A9445F', '#C3627A', '#D9879A'];
            return fases.map((f, idx) => ({
                name: String(f?.fase || '') === 'Recrutamento'
                    ? 'Vagas Preenchidas'
                    : (f?.fase || `Fase ${idx + 1}`),
                value: Math.max(0, Number(f?.valor || 0)),
                color: colors[idx] || colors[colors.length - 1],
            }));
        },
        clienteFasesComPercentual() {
            const fases = this.clienteFases();
            const total = Math.max(1, Number(this.data?.summary?.total_functions || 0));
            return fases.map((f) => ({
                ...f,
                percent: (Number(f.value || 0) / total) * 100,
            }));
        },
        clienteEvolucaoSeries() {
            const trend = Array.isArray(this.data?.trend) ? this.data.trend : [];
            return trend.map((p) => ({
                date: p?.date || '',
                consolidadas: Math.max(0, Number(p?.completed || 0)),
                emEvolucao: Math.max(0, Number(p?.pending || 0)),
                indice: Math.max(0, Math.min(100, Number(p?.progress || 0))),
            }));
        },
        competenciaLabelVisaoCliente() {
            const raw = String(this.competencia || '').trim();
            if (!raw) return 'N/A';
            const match = raw.match(/^(\d{4})-(\d{2})$/);
            if (!match) return raw;
            const meses = [
                'JANEIRO', 'FEVEREIRO', 'MARCO', 'ABRIL', 'MAIO', 'JUNHO',
                'JULHO', 'AGOSTO', 'SETEMBRO', 'OUTUBRO', 'NOVEMBRO', 'DEZEMBRO',
            ];
            const ano = match[1];
            const mesIdx = Math.max(1, Math.min(12, Number(match[2]))) - 1;
            return `${meses[mesIdx]} / ${ano}`;
        },
        clienteEvolucaoResumo() {
            const series = this.clienteEvolucaoSeries();
            if (series.length === 0) {
                return {
                    mapeadas: 0,
                    consolidadas: 0,
                    emEvolucao: 0,
                    indice: 0,
                    coberturaMonitorada: 0,
                };
            }
            const last = series[series.length - 1];
            const mapeadas = Math.max(0, Math.round(last.consolidadas + last.emEvolucao));
            const indice = mapeadas > 0 ? (last.consolidadas / mapeadas) * 100 : 0;
            return {
                mapeadas,
                consolidadas: Math.round(last.consolidadas),
                emEvolucao: Math.round(last.emEvolucao),
                indice: Math.round(indice * 10) / 10,
                coberturaMonitorada: mapeadas > 0 ? 100 : 0,
            };
        },
        clienteCoberturaFrentes() {
            const fases = Array.isArray(this.data?.fase_atual) ? this.data.fase_atual : [];
            const totalMapeadas = Math.max(0, Number(this.data?.summary?.total_functions || 0));
            return fases.map((f) => {
                const monitoradas = Math.max(0, Number(f?.valor || 0));
                const cobertura = totalMapeadas > 0 ? (monitoradas / totalMapeadas) * 100 : 0;
                return {
                    frente: String(f?.fase || 'Frente'),
                    monitoradas,
                    cobertura: Math.max(0, Math.min(100, Math.round(cobertura * 10) / 10)),
                };
            });
        },
        clienteCoberturaResumo() {
            const grupos = this.clienteCoberturaGrupos();
            const mapeadas = grupos.reduce((acc, g) => acc + Number(g.mapeadas || 0), 0);
            const monitoradas = grupos.reduce((acc, g) => acc + Number(g.monitoradas || 0), 0);
            const frentesAcompanhadas = grupos.length;
            const coberturaOperacional = mapeadas > 0 ? (monitoradas / mapeadas) * 100 : 0;
            return {
                mapeadas: Math.round(mapeadas),
                monitoradas: Math.round(monitoradas),
                coberturaOperacional: Math.max(0, Math.min(100, Math.round(coberturaOperacional * 10) / 10)),
                frentesAcompanhadas,
            };
        },
        /** Agrega `ranking` (API) por função/código — base real para mapa de consolidação e cobertura. */
        clienteRankingPorFuncaoAgg() {
            const ranking = Array.isArray(this.data?.ranking) ? this.data.ranking : [];
            const map = new Map();
            ranking.forEach((r) => {
                const funcaoRaw = String(r?.funcao || r?.function || 'Função').trim() || 'Função';
                const codigoRaw = r?.codigo != null && String(r.codigo).trim() !== '' ? String(r.codigo).trim() : '';
                const key = codigoRaw ? `${codigoRaw}\u0000${funcaoRaw}` : funcaoRaw;
                const total = Math.max(0, Math.round(Number(r?.pgu ?? r?.qty ?? 0)));
                const consolidadas = Math.max(0, Math.round(Number(r?.completed ?? 0)));
                if (total <= 0) {
                    return;
                }
                const cur = map.get(key);
                if (cur) {
                    cur.total += total;
                    cur.consolidadas += consolidadas;
                } else {
                    map.set(key, {
                        codigo: codigoRaw || null,
                        funcao: funcaoRaw,
                        total,
                        consolidadas,
                    });
                }
            });
            return [...map.values()]
                .map((row) => ({
                    ...row,
                    consolidadas: Math.min(row.consolidadas, row.total),
                }))
                .filter((row) => row.total > 0)
                .sort((a, b) => b.total - a.total);
        },
        clienteLabelFuncaoAgg(row) {
            if (row.codigo) {
                return `${row.codigo} - ${row.funcao}`;
            }
            return row.funcao;
        },
        clienteCoberturaGrupos() {
            const baseRows = this.clienteRankingPorFuncaoAgg().map((row) => {
                const mapeadas = row.total;
                const monitoradas = row.consolidadas;
                const cobertura = mapeadas > 0 ? (monitoradas / mapeadas) * 100 : 0;
                return {
                    grupo: this.clienteLabelFuncaoAgg(row),
                    mapeadas,
                    monitoradas,
                    cobertura: Math.max(0, Math.min(100, Math.round(cobertura * 10) / 10)),
                    status: monitoradas >= mapeadas ? 'Monitorado' : 'Parcial',
                };
            });

            if (baseRows.length <= 6) {
                return baseRows;
            }

            const top = baseRows.slice(0, 5);
            const others = baseRows.slice(5);
            const mapeadasOutras = others.reduce((acc, r) => acc + r.mapeadas, 0);
            const monitoradasOutras = others.reduce((acc, r) => acc + r.monitoradas, 0);
            top.push({
                grupo: 'Outras Funções',
                mapeadas: mapeadasOutras,
                monitoradas: monitoradasOutras,
                cobertura: mapeadasOutras > 0 ? Math.round((monitoradasOutras / mapeadasOutras) * 1000) / 10 : 0,
                status: monitoradasOutras >= mapeadasOutras ? 'Monitorado' : 'Parcial',
            });
            return top;
        },
        clienteConsolidacaoRows() {
            return this.clienteRankingPorFuncaoAgg().map((row) => {
                const total = row.total;
                const consolidadas = row.consolidadas;
                const emEvolucao = Math.max(0, total - consolidadas);
                const indice = total > 0 ? (consolidadas / total) * 100 : 0;
                return {
                    funcao: this.clienteLabelFuncaoAgg(row),
                    total,
                    consolidadas,
                    emEvolucao,
                    indice: Math.round(indice * 10) / 10,
                };
            });
        },
        clienteConsolidacaoResumo() {
            const rows = this.clienteConsolidacaoRows();
            const mapeadas = rows.reduce((acc, r) => acc + r.total, 0);
            const consolidadas = rows.reduce((acc, r) => acc + r.consolidadas, 0);
            const emEvolucao = rows.reduce((acc, r) => acc + r.emEvolucao, 0);
            const indice = mapeadas > 0 ? (consolidadas / mapeadas) * 100 : 0;
            const delta = Number(this.data?.summary?.progress_delta || 0);
            const funcoesMonitoradas = rows.length;
            const funcoes100 = rows.filter((r) => r.total > 0 && r.consolidadas >= r.total).length;
            return {
                mapeadas: Math.round(mapeadas),
                consolidadas: Math.round(consolidadas),
                emEvolucao: Math.round(emEvolucao),
                coberturaMonitorada: mapeadas > 0 ? 100 : 0,
                indice: Math.round(indice * 10) / 10,
                delta: Math.round(delta * 10) / 10,
                funcoesMonitoradas,
                funcoes100,
            };
        },
        /** Faixas do donut (exceção de cor: paleta semântica verde → vermelho). */
        clienteConsolidacaoFaixasDonutMeta() {
            return [
                { key: '100', label: '100% concluídas', color: '#14532D' },
                { key: '75', label: '75% a 99%', color: '#4ADE80' },
                { key: '50', label: '50% a 74%', color: '#EAB308' },
                { key: '25', label: '25% a 49%', color: '#FB923C' },
                { key: 'low', label: '0% a 24%', color: '#EF4444' },
            ];
        },
        clienteConsolidacaoFaixaIndiceRow(r) {
            if (r.total <= 0) return null;
            if (r.consolidadas >= r.total) return '100';
            const i = Number(r.indice);
            if (i >= 75) return '75';
            if (i >= 50) return '50';
            if (i >= 25) return '25';
            return 'low';
        },
        clienteConsolidacaoFaixasDonut() {
            const rows = this.clienteConsolidacaoRows();
            const meta = this.clienteConsolidacaoFaixasDonutMeta();
            const counts = { 100: 0, 75: 0, 50: 0, 25: 0, low: 0 };
            rows.forEach((r) => {
                const k = this.clienteConsolidacaoFaixaIndiceRow(r);
                if (k != null) counts[k] += 1;
            });
            const totalFuncs = rows.length;
            return meta.map((m) => {
                const v = counts[m.key];
                const pct = totalFuncs > 0 ? (v / totalFuncs) * 100 : 0;
                return {
                    ...m,
                    value: v,
                    pctOfFuncs: pct,
                };
            });
        },
        clienteConsolidacaoTop10PorIndice() {
            return [...this.clienteConsolidacaoRows()]
                .sort((a, b) => b.indice - a.indice)
                .slice(0, 10);
        },
        clienteConsolidacaoStatusFuncao(row) {
            if (row.total > 0 && row.consolidadas >= row.total) return 'Concluída';
            return 'Em evolução';
        },
        /**
         * Progresso consolidado do ciclo (vagas): vagas concluídas ÷ vagas mapeadas (summary).
         * Usa `vagas_concluidas`; não usar `completed_functions` (subconjunto legado).
         */
        clienteProgressoConsolidadoCicloPct() {
            const summary = this.data?.summary || {};
            const mapeadas = Math.max(0, Number(summary.total_functions || 0));
            const consolidadas = Math.max(
                0,
                Number(summary.vagas_concluidas ?? summary.completed_functions ?? 0),
            );
            return mapeadas > 0 ? Math.round((consolidadas / mapeadas) * 1000) / 10 : 0;
        },
        /** % de funções (agregadas) com 100% das vagas da função consolidadas. */
        clientePctFuncoes100Concluidas() {
            const rows = this.clienteConsolidacaoRows();
            const n = rows.length;
            if (n === 0) return 0;
            const n100 = rows.filter((r) => r.total > 0 && r.consolidadas >= r.total).length;
            return Math.round((n100 / n) * 1000) / 10;
        },
        /** % de funções com índice de consolidação > 50%. */
        clientePctFuncoesMaisDe50Consolidacao() {
            const rows = this.clienteConsolidacaoRows();
            const n = rows.length;
            if (n === 0) return 0;
            const n50 = rows.filter((r) => r.indice > 50).length;
            return Math.round((n50 / n) * 1000) / 10;
        },
        /** Duas linhas do rodapé do widget donut (layout igual ao mock). */
        clienteConsolidacaoDonutResumoLinhas() {
            if (this.clienteConsolidacaoRows().length === 0) {
                return { linha1: '', linha2: '' };
            }
            return {
                linha1: `${this.formatPctPtBr(this.clientePctFuncoes100Concluidas())}% das funções já estão 100% concluídas.`,
                linha2: `${this.formatPctPtBr(this.clientePctFuncoesMaisDe50Consolidacao())}% das funções têm mais de 50% de consolidação.`,
            };
        },
        clienteCicloResumo() {
            const p = this.clientePanorama();
            const mapeadas = Math.max(0, Number(p.mapeadas || 0));
            const consolidadas = Math.max(0, Number(p.consolidadas || 0));
            const progressoAtual = this.clienteProgressoConsolidadoCicloPct();
            const hoje = new Date();
            const hojeTxt = `${String(hoje.getDate()).padStart(2, '0')}/${String(hoje.getMonth() + 1).padStart(2, '0')}`;
            const dataLimiteTxt = this.formatDateShort(this.dataLimite) || '--/--';
            const inicioTxt = this.inicioCicloLabel();
            const posHoje = this.todayPositionPercent();
            const diasRestantes = this.daysUntilDeadline();
            const pendentes = Math.max(0, mapeadas - consolidadas);
            const necessarioDia = diasRestantes > 0 ? pendentes / diasRestantes : 0;
            const previstoDia = this.currentDailyProgress();
            const esperado = this.expectedProgressToday();
            const situacaoNoPrazo = progressoAtual + 0.01 >= Math.max(0, esperado - 5);
            return {
                contrato: this.contrato || '—',
                competencia: this.competenciaLabelVisaoCliente(),
                mapeadas,
                consolidadas,
                emEvolucao: p.emEvolucao,
                progressoAtual: Math.round(progressoAtual * 10) / 10,
                progressoTexto: `${Math.round(consolidadas)}/${Math.round(mapeadas)}`,
                hoje: hojeTxt,
                dataLimite: dataLimiteTxt,
                inicioCiclo: inicioTxt,
                hojePos: posHoje,
                diasRestantes,
                pendentes,
                necessarioDia: Math.round(necessarioDia * 10) / 10,
                previstoDia: Math.round(previstoDia * 10) / 10,
                esperadoHoje: Math.round(esperado * 10) / 10,
                situacaoNoPrazo,
            };
        },
        /**
         * Referência de prazos de mobilização (valores acordados para leitura executiva;
         * não substitui regras contratuais específicas).
         */
        clienteCicloSlaReferencia() {
            const prazoDataLimiteDias = 25;
            const diasSgcAteLiberacaoMin = 3;
            const diasSgcAteLiberacaoMax = 3;
            const diasAceiteAteSgc = prazoDataLimiteDias - diasSgcAteLiberacaoMax;
            return {
                diasAceiteAteSgc,
                diasSgcAteLiberacaoMin,
                diasSgcAteLiberacaoMax,
                janelaTotalMin: prazoDataLimiteDias,
                janelaTotalMax: prazoDataLimiteDias,
            };
        },
        /** Data limite em DD/MM/AAAA (para textos longos no card do ciclo). */
        clienteCicloDataLimiteLonga() {
            const m = String(this.dataLimite || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return this.clienteCicloResumo().dataLimite;
            return `${m[3]}/${m[2]}/${m[1]}`;
        },
        /** Status visual da etapa (legenda: concluído / andamento / a iniciar). */
        clienteMaturidadeEtapaStatus(etapa) {
            const v = Number(etapa?.value || 0);
            const t = Number(etapa?.total || 0);
            const p = Number(etapa?.pct || 0);
            if (t > 0 && v >= t) return 'concluido';
            if (p >= 99.5) return 'concluido';
            if (v <= 0 && p <= 0) return 'iniciar';
            return 'andamento';
        },
        clienteMaturidadeTotalVagas() {
            const explicit = Math.round(Number(this.data?.maturidade_total_vagas ?? 0));
            if (explicit > 0) {
                return explicit;
            }
            const kpis = this.data?.summary?.kpis_mao_de_obra_itens || {};
            let t = Math.round(Number(kpis.vagas_pgu_previstas || 0));
            if (!t) {
                t = Math.round(Number(this.data?.mao_de_obra?.mobilizacao || 0));
            }
            if (!t) {
                t = Math.round(Number(this.data?.summary?.total_functions || 0));
            }
            return Math.max(0, t);
        },
        clienteMaturidadeEtapas() {
            const fases = Array.isArray(this.data?.fase_atual) ? this.data.fase_atual : [];
            const getFaseValor = (nome) => {
                const found = fases.find((f) => String(f?.fase || '') === nome);
                return Math.max(0, Number(found?.valor || 0));
            };
            const aprovados = getFaseValor('Recrutamento');
            const exameMedico = getFaseValor('Exame Médico');
            const treinamentos = getFaseValor('Treinamentos');
            const assinatura = getFaseValor('Assinatura documental');
            const sgc = getFaseValor('Postagem SGC');
            const liberacao = getFaseValor('Liberação');
            const totalVagas = this.clienteMaturidadeTotalVagas();
            const maxReal = Math.max(aprovados, exameMedico, treinamentos, assinatura, sgc, liberacao, 1);
            const base = totalVagas > 0 ? totalVagas : maxReal;
            const toPct = (v) => (base > 0 ? Math.round(((v / base) * 100) * 10) / 10 : 0);
            return [
                { key: 'aprovados', label: 'Vagas Preenchidas', value: Math.round(aprovados), total: base, pct: toPct(aprovados), color: '#6F1731' },
                { key: 'exame_medico', label: 'Exame Médico', value: Math.round(exameMedico), total: base, pct: toPct(exameMedico), color: '#73203D' },
                { key: 'treinamentos', label: 'Treinamentos', value: Math.round(treinamentos), total: base, pct: toPct(treinamentos), color: '#8B2C4A' },
                { key: 'assinatura', label: 'Assinatura documental', value: Math.round(assinatura), total: base, pct: toPct(assinatura), color: '#A9445F' },
                { key: 'sgc', label: 'SGC', value: Math.round(sgc), total: base, pct: toPct(sgc), color: '#C3627A' },
                { key: 'liberacao', label: 'Liberação', value: Math.round(liberacao), total: base, pct: toPct(liberacao), color: '#D9879A' },
            ];
        },
        clienteMaturidadeResumo() {
            const etapas = this.clienteMaturidadeEtapas();
            const totalVagas = etapas[0]?.total ?? this.clienteMaturidadeTotalVagas();
            const aprovados = etapas[0]?.value || 0;
            const exameMedico = etapas[1]?.value || 0;
            const treinamentos = etapas[2]?.value || 0;
            const assinatura = etapas[3]?.value || 0;
            const sgc = etapas[4]?.value || 0;
            const liberacao = etapas[5]?.value || 0;
            const denom = totalVagas > 0 ? totalVagas : Math.max(1, aprovados);
            const maturidade = denom > 0 ? (liberacao / denom) * 100 : 0;
            return {
                totalVagas: totalVagas > 0 ? totalVagas : denom,
                aprovados,
                exameMedico,
                treinamentos,
                assinatura,
                sgc,
                liberacao,
                maturidade: Math.round(maturidade * 10) / 10,
            };
        },
        /** Funil «Avanço de Contratações»: linhas do gráfico ordenadas por volume (maior primeiro). */
        clienteContratacoesFunilChartRows() {
            const itens = [...(this.data?.contratacoes_funil?.itens || [])];
            const totalGeral = Math.max(0, Number(this.data?.contratacoes_funil?.total || 0));
            const sorted = [...itens].sort((a, b) => Number(b.valor || 0) - Number(a.valor || 0));
            const maxVal = Math.max(...sorted.map((i) => Number(i.valor || 0)), 1);
            return sorted.map((it, idx) => ({
                ...it,
                rank: idx + 1,
                pctDoTotal: totalGeral > 0 ? (Number(it.valor || 0) / totalGeral) * 100 : 0,
                barWidthPct: (Number(it.valor || 0) / maxVal) * 100,
            }));
        },
        /** Bullets da «Leitura executiva» (top etapas por volume). */
        clienteContratacoesLeituraExecutiva() {
            const totalGeral = Math.max(0, Number(this.data?.contratacoes_funil?.total || 0));
            if (totalGeral <= 0) {
                return ['Sem candidatos aprovados no recorte da competência.'];
            }
            const linhas = this.clienteContratacoesFunilChartRows().slice(0, 4).map((it) => {
                const pct = (Number(it.valor || 0) / totalGeral) * 100;
                const nome = String(it.label || '').replace(/\s+/g, ' ').trim();
                return `${nome} concentra ${this.formatQtyPtBr(it.valor)} vagas (${this.formatPctPtBr(pct)}%).`;
            });
            return linhas.length ? linhas : ['Distribuição equilibrada entre as etapas monitoradas.'];
        },
        /** Base inicial do ciclo para comparativos por período (início -> agora). */
        clienteDestaquesBaselineInicioCiclo() {
            const trend = Array.isArray(this.data?.trend) ? this.data.trend : [];
            const ft = Array.isArray(this.data?.fase_trend) ? this.data.fase_trend : [];
            if (trend.length < 2 && ft.length < 2) {
                // Sem histórico comparável no período: baseline parte de zero.
                return {
                    consolidadas: 0,
                    emEvolucao: 0,
                    progresso: 0,
                    recrutamento: 0,
                    exameMedico: 0,
                    treinamentos: 0,
                    assinatura: 0,
                    sgc: 0,
                    liberacao: 0,
                };
            }
            const t0 = trend[0] || {};
            const f0 = ft[0] || {};
            return {
                consolidadas: Math.max(0, Math.round(Number(t0.completed || 0))),
                emEvolucao: Math.max(0, Math.round(Number(t0.pending || 0))),
                progresso: Math.max(0, Math.min(100, Number(t0.progress || 0))),
                recrutamento: Math.max(0, Math.round(Number(f0.recrutamento || 0))),
                exameMedico: Math.max(0, Math.round(Number(f0.exame_medico || 0))),
                treinamentos: Math.max(0, Math.round(Number(f0.treinamentos || 0))),
                assinatura: Math.max(0, Math.round(Number(f0.assinatura_documental || 0))),
                sgc: Math.max(0, Math.round(Number(f0.sgc || 0))),
                liberacao: Math.max(0, Math.round(Number(f0.liberacao || 0))),
            };
        },
        /** Comparativo do período do ciclo (início do ciclo -> posição atual). */
        clienteDestaquesDeltaUltimoPeriodo() {
            const baseline = this.clienteDestaquesBaselineInicioCiclo();
            const resumo = this.clienteCicloResumo();
            const etapas = this.clienteMaturidadeEtapas();
            const z = {
                dCons: 0,
                dPend: 0,
                dProg: 0,
                dRecrutamento: 0,
                dExameMedico: 0,
                dTreinamentos: 0,
                dAssinaturaDocumental: 0,
                dTreinPipe: 0,
                dSgc: 0,
                dLib: 0,
            };
            z.dCons = Math.round(Number(resumo.consolidadas || 0) - baseline.consolidadas);
            z.dPend = Math.round(Number(resumo.emEvolucao || 0) - baseline.emEvolucao);
            z.dProg = Math.round((Number(resumo.progressoAtual || 0) - baseline.progresso) * 10) / 10;
            z.dRecrutamento = Math.round(Number(etapas[0]?.value || 0) - baseline.recrutamento);
            z.dExameMedico = Math.round(Number(etapas[1]?.value || 0) - baseline.exameMedico);
            z.dTreinamentos = Math.round(Number(etapas[2]?.value || 0) - baseline.treinamentos);
            z.dAssinaturaDocumental = Math.round(Number(etapas[3]?.value || 0) - baseline.assinatura);
            z.dTreinPipe = z.dTreinamentos;
            z.dSgc = Math.round(Number(etapas[4]?.value || 0) - baseline.sgc);
            z.dLib = Math.round(Number(etapas[5]?.value || 0) - baseline.liberacao);
            return z;
        },
        clienteDestaquesTreinamentosCount() {
            const fases = Array.isArray(this.data?.fase_atual) ? this.data.fase_atual : [];
            const get = (nome) => {
                const x = fases.find((f) => String(f?.fase || '') === nome);
                return Math.max(0, Number(x?.valor || 0));
            };
            return Math.round(get('Treinamentos'));
        },
        clienteDestaquesPrincipaisAvancos() {
            const d = this.clienteDestaquesDeltaUltimoPeriodo();
            const out = [];
            out.push({
                titulo: `Aumento de ${this.formatQtyPtBr(Math.max(0, d.dCons))} vagas consolidadas.`,
                desc: 'Comparativo entre o início do ciclo e a posição atual.',
            });
            const evoTxt = d.dPend >= 0 ? `Aumento de ${this.formatQtyPtBr(d.dPend)} vagas em carência operacional (pendências PGU − Pré no período).` : `Redução de ${this.formatQtyPtBr(Math.abs(d.dPend))} vagas na fila de pendências.`;
            out.push({
                titulo: evoTxt,
                desc: 'Leitura do período completo do ciclo contratual.',
            });
            out.push({
                titulo: `Variação de ${this.formatPctPtBr(d.dProg)} p.p. no progresso médio do ranking.`,
                desc: 'Variação acumulada no período do ciclo (início -> atual).',
            });
            out.push({
                titulo: `Liberações: ${d.dLib >= 0 ? '+' : '−'}${this.formatQtyPtBr(Math.abs(d.dLib))} candidatos na etapa de liberação.`,
                desc: 'Variação acumulada das contagens de maturidade no período.',
            });
            return out;
        },
        /** Exibe data da série mensal (m/Y) como dd/mm/aaaa (1º dia do mês). */
        clienteDestaquesMovimentacaoDataLabel(raw) {
            const s = String(raw || '').trim();
            const m = s.match(/^(\d{1,2})\/(\d{4})$/);
            if (!m) {
                return s;
            }
            return `01/${m[1].padStart(2, '0')}/${m[2]}`;
        },
        clienteDestaquesMovimentacoesFooter() {
            const n = this.clienteDestaquesMovimentacoesRows().length;
            if (n === 0) {
                return 'Sem movimentações reais registradas no período do ciclo para este recorte.';
            }
            return `Mostrando ${n} movimentação(ões) reais registradas no período do ciclo.`;
        },
        clienteDestaquesMovimentacoesRows() {
            const apiRows = Array.isArray(this.data?.cycle_movements) ? this.data.cycle_movements : [];
            if (apiRows.length > 0) {
                return apiRows.map((row) => ({
                    data: String(row?.date || ''),
                    mov: String(row?.mov || 'Atualização de ciclo'),
                    qtd: String(row?.qtd || '+0'),
                    impactoPos: Boolean(row?.impactoPos),
                    impacto: String(row?.impacto || '+0,0 p.p.'),
                }));
            }

            const baseline = this.clienteDestaquesBaselineInicioCiclo();
            const resumo = this.clienteCicloResumo();
            const inicio = this.parseDateAny(this.competencia ? `${this.competencia}-01` : null)
                || this.parseDateAny(this.data?.summary?.cycle_start_date);
            const dataLimite = this.parseDateAny(this.dataLimite);
            const inicioLabel = this.formatDateBR(inicio);
            const hojeLabel = this.formatDateBR(new Date());
            const limiteLabel = this.formatDateBR(dataLimite);

            const deltaCons = Math.round(Number(resumo.consolidadas || 0) - baseline.consolidadas);
            const deltaProg = Math.round((Number(resumo.progressoAtual || 0) - baseline.progresso) * 10) / 10;
            const faltaCons = Math.max(0, Math.round(Number(resumo.mapeadas || 0) - Number(resumo.consolidadas || 0)));
            const faltaProg = Math.max(0, Math.round((100 - Number(resumo.progressoAtual || 0)) * 10) / 10);

            return [
                {
                    data: inicioLabel,
                    mov: 'Início do período',
                    qtd: `+${this.formatQtyPtBr(baseline.consolidadas)}`,
                    impactoPos: baseline.progresso >= 0,
                    impacto: `+${this.formatPctPtBr(baseline.progresso)} p.p.`,
                },
                {
                    data: hojeLabel,
                    mov: 'Posição atual do ciclo',
                    qtd: `${deltaCons >= 0 ? '+' : '−'}${this.formatQtyPtBr(Math.abs(deltaCons))}`,
                    impactoPos: deltaProg >= 0,
                    impacto: `${deltaProg >= 0 ? '+' : ''}${this.formatPctPtBr(deltaProg)} p.p.`,
                },
                {
                    data: limiteLabel,
                    mov: 'Meta até a data limite',
                    qtd: `+${this.formatQtyPtBr(faltaCons)}`,
                    impactoPos: true,
                    impacto: `+${this.formatPctPtBr(faltaProg)} p.p.`,
                },
            ];
        },
        clienteDestaquesPontosAtencao() {
            const items = [];
            const rows = [...this.clienteConsolidacaoRows()]
                .filter((r) => r.total > 0 && r.indice < 100)
                .sort((a, b) => a.indice - b.indice)
                .slice(0, 2);
            if (rows.length > 0) {
                const f = rows.map((r) => `${r.funcao.split(' - ').pop() || r.funcao} (${this.formatPctPtBr(r.indice)}%)`).join(' e ');
                items.push({
                    titulo: 'Funções com menor índice de consolidação.',
                    texto: f,
                });
            }
            const crit = Math.max(0, Number(this.data?.summary?.critical_functions || 0));
            if (crit > 0) {
                items.push({
                    titulo: 'Funções em situação crítica de avanço.',
                    texto: `${this.formatQtyPtBr(crit)} função(ões) com pendência ou avanço aquém do esperado no ranking.`,
                });
            }
            const atr = Math.max(0, Number(this.data?.summary?.itens_atrasados_fase2 || 0));
            if (atr > 0) {
                items.push({
                    titulo: 'Itens com evolução pendente.',
                    texto: `${this.formatQtyPtBr(atr)} função(ões) ainda sem 100% de avanço na fase PGU.`,
                });
            }
            const risk = String(this.data?.summary?.deadline_risk_label || '');
            const bad = ['Alto', 'Elevado', 'Atrasado'].includes(risk);
            if (bad && risk) {
                items.push({
                    titulo: 'Prazo contratual.',
                    texto: `Risco classificado como ${risk} em relação à data limite e ao progresso atual.`,
                });
            }
            if (items.length === 0) {
                items.push({
                    titulo: 'Situação estável.',
                    texto: 'Não há alertas automáticos adicionais; mantenha o ritmo até a data limite.',
                });
            }
            return items.slice(0, 4);
        },
        clienteDestaquesContribuicaoEtapas() {
            const e = this.clienteMaturidadeEtapas();
            const baseline = this.clienteDestaquesBaselineInicioCiclo();
            const resumo = this.clienteCicloResumo();
            const totalPpPeriodo = Math.max(0, Math.round((Number(resumo.progressoAtual || 0) - Number(baseline.progresso || 0)) * 10) / 10);
            const parts = [
                { label: 'Exame médico', value: Math.max(0, Number(e[1]?.value || 0) - Number(baseline.exameMedico || 0)), color: '#14532D' },
                { label: 'Treinamentos', value: Math.max(0, Number(e[2]?.value || 0) - Number(baseline.treinamentos || 0)), color: '#22C55E' },
                { label: 'Assinatura documental', value: Math.max(0, Number(e[3]?.value || 0) - Number(baseline.assinatura || 0)), color: '#4ADE80' },
                { label: 'SGC', value: Math.max(0, Number(e[4]?.value || 0) - Number(baseline.sgc || 0)), color: '#F59E0B' },
                { label: 'Liberação', value: Math.max(0, Number(e[5]?.value || 0) - Number(baseline.liberacao || 0)), color: '#9333EA' },
            ];
            const sum = parts.reduce((a, p) => a + p.value, 0);
            return parts.map((p) => ({
                ...p,
                pp: (sum > 0 && totalPpPeriodo > 0) ? Math.round(((p.value / sum) * totalPpPeriodo) * 10) / 10 : 0,
                pctShare: (sum > 0 && totalPpPeriodo > 0) ? Math.round(((p.value / sum) * 100) * 10) / 10 : 0,
            }));
        },
        clienteDestaquesResumoOperacionalTexto() {
            return this.clienteCicloResumo().situacaoNoPrazo
                ? 'O ciclo está avançando conforme o planejado.'
                : 'Atenção: ritmo abaixo do esperado para a posição atual no calendário do contrato.';
        },
        clientePlanoDataLimiteCompleta() {
            const v = String(this.dataLimite || '').trim();
            const m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return this.clienteCicloResumo().dataLimite;
            return `${m[3]}/${m[2]}/${m[1]}`;
        },
        clientePlanoFasesConcluidasContagem() {
            const e = this.clienteMaturidadeEtapas();
            const n = e.filter((et) => this.clienteMaturidadeEtapaStatus(et) === 'concluido').length;
            return { concluidas: n, total: e.length || 6 };
        },
        clientePlanoSlaDatas() {
            const ref = this.clienteCicloSlaReferencia();
            const start = this.parseDateAny(this.data?.summary?.cycle_start_date)
                || this.parseDateAny(this.competencia ? `${this.competencia}-01` : null);
            if (!start) return null;
            const addDays = (base, days) => this.clientePlanoAddBusinessDays(base, days);
            return {
                ...ref,
                inicio: start,
                sgc: addDays(start, ref.diasAceiteAteSgc),
                liberacaoMin: addDays(start, ref.janelaTotalMin),
                liberacaoMax: addDays(start, ref.janelaTotalMax),
            };
        },
        clientePlanoFmtDiaMes(date) {
            if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '--/--';
            return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')}`;
        },
        clientePlanoAddBusinessDays(baseDate, days) {
            const base = (baseDate instanceof Date && !Number.isNaN(baseDate.getTime()))
                ? new Date(baseDate.getFullYear(), baseDate.getMonth(), baseDate.getDate())
                : null;
            if (!base) return null;
            const target = Math.max(0, Math.round(Number(days) || 0));
            if (target === 0) return base;
            let acc = 0;
            while (acc < target) {
                base.setDate(base.getDate() + 1);
                const weekDay = base.getDay();
                if (weekDay !== 0 && weekDay !== 6) {
                    acc += 1;
                }
            }
            return base;
        },
        clientePlanoSlaJanelaDiasLabel() {
            const ref = this.clienteCicloSlaReferencia();
            return ref.janelaTotalMin === ref.janelaTotalMax
                ? `${ref.janelaTotalMin} dias`
                : `${ref.janelaTotalMin} a ${ref.janelaTotalMax} dias`;
        },
        clientePlanoSlaJanelaDatasLabel() {
            const sla = this.clientePlanoSlaDatas();
            if (!sla) {
                const ref = this.clienteCicloSlaReferencia();
                return ref.janelaTotalMin === ref.janelaTotalMax
                    ? `D+${ref.janelaTotalMin}`
                    : `D+${ref.janelaTotalMin} a D+${ref.janelaTotalMax}`;
            }
            return `${this.clientePlanoFmtDiaMes(sla.liberacaoMin)} a ${this.clientePlanoFmtDiaMes(sla.liberacaoMax)}`;
        },
        /** Marcos ao longo do ciclo (padrão: 6 etapas de maturidade PGU). */
        clientePlanoMilestonePrazo(idx, nSteps = 6) {
            const sla = this.clientePlanoSlaDatas();
            if (sla) {
                const offsets = [3, 7, 11, 13, 15];
                if (idx >= 0 && idx <= 4) {
                    return `Até ${this.clientePlanoFmtDiaMes(this.clientePlanoAddBusinessDays(sla.inicio, offsets[idx]))}`;
                }
                if (idx === 5) {
                    return `${this.clientePlanoFmtDiaMes(sla.liberacaoMin)} a ${this.clientePlanoFmtDiaMes(sla.liberacaoMax)}`;
                }
            }
            const start = this.parseDateAny(this.data?.summary?.cycle_start_date)
                || this.parseDateAny(this.competencia ? `${this.competencia}-01` : null);
            const end = this.parseDateAny(this.dataLimite);
            if (!start || !end) return '—';
            const r = (idx + 1) / nSteps;
            const t = start.getTime() + (end.getTime() - start.getTime()) * r;
            const d = new Date(t);
            return `Até ${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`;
        },
        /** Todas as etapas do ciclo de recrutamento/maturidade no roteiro. */
        clientePlanoRoteiroEtapas() {
            const etapas = this.clienteMaturidadeEtapas();
            const map = [
                { step: 1, titulo: 'Vagas Preenchidas', keyIdx: 0 },
                { step: 2, titulo: 'Exame médico', keyIdx: 1 },
                { step: 3, titulo: 'Treinamentos', keyIdx: 2 },
                { step: 4, titulo: 'Assinatura documental', keyIdx: 3 },
                { step: 5, titulo: 'Postagem SGC', keyIdx: 4 },
                { step: 6, titulo: 'Liberação', keyIdx: 5 },
            ];
            const tituloRoteiroMap = {
                Recrutamento: 'Vagas Preenchidas',
                'Vagas Preenchidas': 'Vagas Preenchidas',
                'Exame médico': 'Exame médico',
                Treinamentos: 'Treinamentos',
                'Assinatura documental': 'Assinatura de contrato',
                'Postagem SGC': 'SGC',
                Liberação: 'Liberação',
            };
            const n = 6;
            return map.map((L, i) => {
                const e = etapas[L.keyIdx] || { pct: 0 };
                const st = this.clienteMaturidadeEtapaStatus(e);
                const statusTxt = st === 'concluido' ? 'Concluída' : st === 'iniciar' ? 'A iniciar' : 'Em andamento';
                return {
                    step: L.step,
                    titulo: L.titulo,
                    tituloRoteiro: tituloRoteiroMap[L.titulo] || L.titulo,
                    pct: Number(e.pct || 0),
                    statusKey: st,
                    statusTxt,
                    prazoAte: this.clientePlanoMilestonePrazo(i, n),
                };
            });
        },
        /** Largura % da barra do roteiro até o centro da última etapa já iniciada ou concluída. */
        clientePlanoRoteiroLinhaPct() {
            const steps = this.clientePlanoRoteiroEtapas();
            let last = -1;
            steps.forEach((s, i) => {
                if (s.statusKey !== 'iniciar') {
                    last = i;
                }
            });
            if (last < 0 || steps.length === 0) {
                return '0%';
            }
            const frac = (last + 0.5) / steps.length;
            return `${Math.round(frac * 1000) / 10}%`;
        },
        clientePlanoSituacaoCicloLabel() {
            return this.clienteCicloResumo().situacaoNoPrazo ? 'NO PRAZO' : 'ATENÇÃO AO PRAZO';
        },
        clientePlanoAcoesRows() {
            const e = this.clienteMaturidadeEtapas();
            const st = (i) => this.clienteMaturidadeEtapaStatus(e[i]);
            const pill = (i) => {
                const s = st(i);
                if (s === 'concluido') return { text: 'Concluída', class: 'bg-emerald-100 text-emerald-800 ring-emerald-200/80' };
                if (s === 'iniciar') return { text: 'A iniciar', class: 'bg-sky-100 text-sky-800 ring-sky-200/80' };
                return { text: 'Em andamento', class: 'bg-amber-100 text-amber-900 ring-amber-200/80' };
            };
            const impacto = (i) => (i >= 4 && st(i) === 'iniciar' ? 'Crítico' : 'Alto');
            const dl = this.clientePlanoDataLimiteCompleta();
            const sla = this.clientePlanoSlaDatas();
            const examePrazo = sla ? `Até ${this.clientePlanoFmtDiaMes(this.clientePlanoAddBusinessDays(sla.inicio, 7))}` : 'Contínuo';
            const liberacaoPrazo = sla ? `${this.clientePlanoFmtDiaMes(sla.liberacaoMin)} a ${this.clientePlanoFmtDiaMes(sla.liberacaoMax)}` : dl;
            return [
                {
                    acao: 'Exame médico',
                    desc: 'Concluir agendamento e confirmação do exame para candidatos aprovados, com datas registradas no PGU.',
                    resp: 'Equipe de Saúde Ocupacional / RH',
                    prazo: st(1) === 'concluido' ? `Até ${dl}` : examePrazo,
                    pill: pill(1),
                    impacto: impacto(1),
                    icon: 'graduation-cap',
                    iconClass: 'bg-emerald-50 text-emerald-700',
                },
                {
                    acao: 'Treinamentos',
                    desc: 'Concluir capacitações obrigatórias e confirmações de treinamento alinhadas ao calendário do ciclo.',
                    resp: 'Equipe de Treinamentos',
                    prazo: this.clientePlanoMilestonePrazo(2),
                    pill: pill(2),
                    impacto: impacto(2),
                    icon: 'pencil-line',
                    iconClass: 'bg-emerald-50 text-emerald-700',
                },
                {
                    acao: 'Assinatura documental',
                    desc: 'Formalizar contratos e documentação até a etapa de pós-contratação, alinhado ao volume de vagas consolidadas.',
                    resp: 'Gestão PGU / RH',
                    prazo: this.clientePlanoMilestonePrazo(3),
                    pill: pill(3),
                    impacto: impacto(3),
                    icon: 'shield-check',
                    iconClass: 'bg-emerald-50 text-emerald-700',
                },
                {
                    acao: 'Postagem e acompanhamento SGC',
                    desc: 'Garantir envio e validação no SGC para liberação final das vagas em conformidade com o contrato.',
                    resp: 'Equipe de Mobilização',
                    prazo: this.clientePlanoMilestonePrazo(4),
                    pill: pill(4),
                    impacto: impacto(4),
                    icon: 'user-round-check',
                    iconClass: 'bg-sky-50 text-sky-700',
                },
                {
                    acao: 'Liberação de candidatos',
                    desc: 'Concluir liberações pendentes e comunicar as áreas operacionais até a data limite do ciclo.',
                    resp: 'Gestão PGU',
                    prazo: liberacaoPrazo,
                    pill: pill(5),
                    impacto: impacto(5),
                    icon: 'chart-no-axes-column-increasing',
                    iconClass: 'bg-amber-50 text-amber-700',
                },
                {
                    acao: 'Monitoramento executivo',
                    desc: 'Revisão periódica do mapa de consolidação, pendências críticas e ritmo versus curva esperada até a data limite.',
                    resp: 'Liderança do contrato',
                    prazo: 'Contínuo',
                    pill: { text: 'Em andamento', class: 'bg-amber-100 text-amber-900 ring-amber-200/80' },
                    impacto: 'Alto',
                    icon: 'activity',
                    iconClass: 'bg-emerald-50 text-emerald-700',
                },
            ];
        },
        clientePlanoFocosLista() {
            const dl = this.clientePlanoDataLimiteCompleta();
            return [
                { texto: `Priorizar funções com maior volume de vagas em evolução até ${dl}.`, icon: 'target' },
                { texto: `Prazo operacional de liberação previsto para ${this.clientePlanoSlaJanelaDatasLabel()} (${this.clientePlanoSlaJanelaDiasLabel()}).`, icon: 'calendar-clock' },
                { texto: 'Manter cadência de treinamentos e assinaturas alinhada ao calendário do ciclo.', icon: 'pencil-line' },
                { texto: 'Tratar primeiro funções críticas ou com pendência elevada no ranking PGU.', icon: 'shield-check' },
                { texto: 'Garantir comunicação clara com as áreas demandantes sobre marcos de liberação.', icon: 'users-round' },
                { texto: 'Confirmar 100% das liberações antes da data limite contratual.', icon: 'chart-no-axes-column-increasing' },
            ];
        },
        daysUntilDeadline() {
            const m = String(this.dataLimite || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return 0;
            const hoje = new Date();
            const limit = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
            const diff = Math.ceil((limit.getTime() - new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate()).getTime()) / 86400000);
            return Math.max(0, diff);
        },
        currentDailyProgress() {
            const r = this.clienteCicloResumoBase();
            const elapsed = Math.max(1, r.elapsedDays);
            return r.consolidadas / elapsed;
        },
        expectedProgressToday() {
            const r = this.clienteCicloResumoBase();
            if (r.totalDays <= 0) return 0;
            return Math.max(0, Math.min(100, (r.elapsedDays / r.totalDays) * 100));
        },
        clienteCicloResumoBase() {
            const p = this.clientePanorama();
            const mapeadas = Math.max(0, Number(p.mapeadas || 0));
            const consolidadas = Math.max(0, Number(p.consolidadas || 0));
            const start = this.parseDateAny(this.data?.summary?.cycle_start_date) || this.parseDateAny(this.competencia ? `${this.competencia}-01` : null);
            const end = this.parseDateAny(this.dataLimite);
            const now = new Date();
            const startDay = start ? new Date(start.getFullYear(), start.getMonth(), start.getDate()) : now;
            const endDay = end ? new Date(end.getFullYear(), end.getMonth(), end.getDate()) : now;
            const nowDay = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const totalDays = Math.max(1, Math.ceil((endDay.getTime() - startDay.getTime()) / 86400000));
            const elapsedDays = Math.max(1, Math.ceil((Math.min(nowDay.getTime(), endDay.getTime()) - startDay.getTime()) / 86400000));
            return { mapeadas, consolidadas, totalDays, elapsedDays };
        },
        parseDateAny(v) {
            const s = String(v || '');
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return null;
            return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        },
        cicloPeriodoLabel() {
            const c = String(this.competencia || '');
            const m = c.match(/^(\d{4})-(\d{2})$/);
            if (!m) return this.competenciaLabelVisaoCliente();
            return `${m[2]}/${m[1]}`;
        },
        inicioCicloLabel() {
            const fromApi = String(this.data?.summary?.cycle_start_date || '').trim();
            const iso = fromApi.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (iso) return `${iso[3]}/${iso[2]}/${iso[1]}`;
            const c = String(this.competencia || '');
            const mt = c.match(/^(\d{4})-(\d{2})$/);
            if (mt) return `01/${mt[2]}/${mt[1]}`;
            return '--/--/----';
        },
        formatDateShort(raw) {
            const v = String(raw || '').trim();
            const m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!m) return '';
            return `${m[3]}/${m[2]}`;
        },
        todayPositionPercent() {
            const hoje = new Date();
            const c = String(this.competencia || '');
            const cm = c.match(/^(\d{4})-(\d{2})$/);
            const dl = String(this.dataLimite || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!cm || !dl) return 72;
            const inicio = new Date(Number(cm[1]), Number(cm[2]) - 1, 1).getTime();
            const limite = new Date(Number(dl[1]), Number(dl[2]) - 1, Number(dl[3])).getTime();
            const atual = hoje.getTime();
            if (!(limite > inicio)) return 72;
            const pct = ((atual - inicio) / (limite - inicio)) * 100;
            return Math.max(8, Math.min(92, Math.round(pct)));
        },
        parseDateOnly(value) {
            if (!value) return null;

            if (value instanceof Date && !Number.isNaN(value.getTime())) {
                return new Date(value.getFullYear(), value.getMonth(), value.getDate());
            }

            const raw = String(value).trim();
            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);

            if (!match) return null;

            return new Date(
                Number(match[1]),
                Number(match[2]) - 1,
                Number(match[3]),
            );
        },

        formatDateBR(value, withYear = true) {
            const date = this.parseDateOnly(value);

            if (!date) return '—';

            return new Intl.DateTimeFormat('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: withYear ? 'numeric' : undefined,
            }).format(date);
        },

        competenciaInicioDate() {
            const fromSummary = this.parseDateOnly(this.data?.summary?.cycle_start_date || null);
            if (fromSummary) {
                return fromSummary;
            }
            const raw = String(this.competencia || '').trim();
            const match = raw.match(/^(\d{4})-(\d{2})$/);

            if (!match) {
                const today = new Date();
                return new Date(today.getFullYear(), today.getMonth(), 1);
            }

            return new Date(Number(match[1]), Number(match[2]) - 1, 1);
        },

        deadlineDateValue() {
            return this.data?.summary?.deadline_date || this.dataLimite || null;
        },

        daysBetweenDates(start, end) {
            if (!start || !end) return null;

            const msPerDay = 24 * 60 * 60 * 1000;
            const startDate = new Date(start.getFullYear(), start.getMonth(), start.getDate());
            const endDate = new Date(end.getFullYear(), end.getMonth(), end.getDate());

            return Math.ceil((endDate - startDate) / msPerDay);
        },

        clienteCicloSlaResumo() {
            const panorama = this.clientePanorama();
            const ref = this.clienteCicloSlaReferencia();
            const hoje = new Date();
            const hojeDate = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate());

            const inicioCiclo = this.competenciaInicioDate();
            const dataLimiteRaw = this.deadlineDateValue();
            const dataLimite = this.parseDateOnly(dataLimiteRaw);

            const diasRestantes = dataLimite ? this.daysBetweenDates(hojeDate, dataLimite) : null;
            const totalDiasCiclo = dataLimite ? Math.max(1, this.daysBetweenDates(inicioCiclo, dataLimite) ?? 1) : 1;
            const diasDecorridosRaw = this.daysBetweenDates(inicioCiclo, hojeDate);
            const diasDecorridos = Math.max(0, diasDecorridosRaw ?? 0);

            const posicaoHoje = dataLimite
                ? Math.max(0, Math.min(100, (diasDecorridos / totalDiasCiclo) * 100))
                : 0;

            const progresso = Math.max(0, Math.min(100, Number(panorama.pctConsolidada || 0)));
            const aceiteSgcApi = Number(this.data?.summary?.aceite_to_sgc_progress_pct);
            const progressoAceiteSgc = Number.isFinite(aceiteSgcApi)
                ? Math.max(0, Math.min(100, aceiteSgcApi))
                : progresso;

            let statusLabel = 'Em acompanhamento';
            let statusText = 'O ciclo está sendo acompanhado conforme os prazos operacionais e a data limite contratual.';
            let statusTone = 'neutral';

            if (progresso >= 100) {
                statusLabel = 'Concluído';
                statusText = 'Todas as fases do ciclo foram concluídas dentro do acompanhamento previsto.';
                statusTone = 'success';
            } else if (diasRestantes !== null && diasRestantes < 0) {
                statusLabel = 'Prazo encerrado';
                statusText = 'O ciclo ultrapassou a data limite e requer leitura executiva do status atual.';
                statusTone = 'danger';
            } else if (diasRestantes !== null && diasRestantes <= 7) {
                statusLabel = 'Reta final';
                statusText = 'O ciclo está na reta final de acompanhamento até a data limite contratual.';
                statusTone = 'warning';
            } else if (diasRestantes !== null && diasRestantes >= 0) {
                statusLabel = 'No prazo';
                statusText = 'O ciclo está sendo acompanhado com base nos prazos operacionais e na data limite contratual.';
                statusTone = 'success';
            }

            const slaAceiteSgc = `${ref.diasAceiteAteSgc} dias`;
            const slaSgcLiberacao = ref.diasSgcAteLiberacaoMin === ref.diasSgcAteLiberacaoMax
                ? `${ref.diasSgcAteLiberacaoMin} dias`
                : `${ref.diasSgcAteLiberacaoMin} a ${ref.diasSgcAteLiberacaoMax} dias`;
            const janelaTotalSla = ref.janelaTotalMin === ref.janelaTotalMax
                ? `${ref.janelaTotalMin} dias`
                : `${ref.janelaTotalMin} a ${ref.janelaTotalMax} dias`;

            return {
                contrato: this.contrato || '—',
                competencia: this.competenciaLabelVisaoCliente(),

                inicioCicloLabel: this.formatDateBR(inicioCiclo),
                hojeLabel: this.formatDateBR(hojeDate),
                dataLimiteLabel: this.formatDateBR(dataLimiteRaw),

                diasRestantes,
                diasRestantesLabel: diasRestantes === null
                    ? '—'
                    : diasRestantes < 0
                        ? `${Math.abs(diasRestantes)} dia(s) após o prazo`
                        : `${diasRestantes} dia(s)`,

                diasRestantesKpi: diasRestantes === null
                    ? '—'
                    : diasRestantes < 0
                        ? String(Math.abs(diasRestantes))
                        : String(diasRestantes),
                diasRestantesKpiNote: diasRestantes === null
                    ? 'Sem data limite definida'
                    : diasRestantes < 0
                        ? 'dias após o prazo'
                        : 'até a data limite',

                progresso,
                progressoLabel: `${this.formatPctPtBr(progresso)}%`,
                progressoStyle: `width: ${progresso}%`,
                /** Trecho Aceite → Postagem SGC: fluxo RH (5 pesos), média ponderada por quantidade de vaga. */
                progressoAceiteSgc,
                progressoAceiteSgcLabel: `${this.formatPctPtBr(progressoAceiteSgc)}%`,
                progressoAceiteSgcStyle: `width: ${progressoAceiteSgc}%`,
                hojeStyle: `left: ${posicaoHoje}%`,

                vagasMapeadas: Math.round(panorama.mapeadas || 0),
                vagasConsolidadas: Math.round(panorama.consolidadas || 0),
                vagasEmEvolucao: Math.round(panorama.emEvolucao || 0),
                vagasMonitoradasPct: panorama.monitoradas || 0,

                slaAceiteSgc,
                slaSgcLiberacao,
                janelaTotalSla,

                statusLabel,
                statusText,
                statusTone,
            };
        },
        wrapLabelText(text, maxChars = 14) {
            const parts = String(text || '').split(' ');
            const lines = [];
            let current = '';
            parts.forEach((part) => {
                const probe = current ? `${current} ${part}` : part;
                if (probe.length > maxChars && current) {
                    lines.push(current);
                    current = part;
                } else {
                    current = probe;
                }
            });
            if (current) lines.push(current);
            return lines.join('\n');
        },
        vagasConcluidasTotal() {
            const items = this.data?.funcoes_pgu_100;
            if (!Array.isArray(items)) return 0;
            return Math.round(items.reduce((acc, row) => acc + Number(row?.completed || 0), 0));
        },
        initFromDataset() {
            const root = document.querySelector('[data-pgu-dashboard]');
            if (!root) return;
            this.contrato = root.dataset.contrato || '';
            this.competencia = root.dataset.competencia || '';
            this.dataLimite = root.dataset.dataLimite || '';
        },
        async init() {
            this.initFromDataset();
            await this.loadData();
            this.renderCharts();
            this.bindResize();
            this.scheduleChartsResize();
        },
        async loadData() {
            this.loading = true;
            this.error = null;
            try {
                const params = new URLSearchParams({
                    contrato: this.contrato,
                    competencia: this.competencia,
                });
                if (this.dataLimite) params.set('data_limite_etapa_2', this.dataLimite);
                const response = await fetch(`/api/pgu/dashboard?${params.toString()}`);
                if (!response.ok) throw new Error('Falha ao carregar indicadores.');
                this.data = await response.json();
            } catch (e) {
                this.error = e.message || 'Falha ao carregar dashboard.';
            } finally {
                this.loading = false;
                queueMicrotask(() => initAppLucideIcons());
            }
        },
        async refresh() {
            await this.loadData();
            this.disposeCharts();
            this.renderCharts();
            this.scheduleChartsResize();
        },
        disposeCharts() {
            Object.values(this.charts).forEach((chart) => chart?.dispose?.());
            this.charts = {};
        },
        bindResize() {
            window.addEventListener('resize', () => {
                Object.values(this.charts).forEach((chart) => chart?.resize?.());
            });
        },
        /** Após fetch + Alpine, o container às vezes ainda tem largura 0; o ECharts desenha canvas vazio. Dois rAF + resize corrige produção. */
        scheduleChartsResize() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    Object.values(this.charts).forEach((chart) => {
                        if (!chart || (typeof chart.isDisposed === 'function' && chart.isDisposed())) {
                            return;
                        }
                        try {
                            chart.resize();
                        } catch (_) {
                            /* ignore */
                        }
                    });
                });
            });
        },
        sanitizeFilePart(str) {
            return String(str || 'x')
                .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
                .replace(/\s+/g, '_')
                .slice(0, 80);
        },
        exportChartPng(chartId) {
            const chart = this.charts[chartId];
            if (!chart || (typeof chart.isDisposed === 'function' && chart.isDisposed())) {
                window.alert('Gráfico indisponível. Clique em Atualizar e tente de novo.');
                return;
            }
            try {
                const url = chart.getDataURL({
                    type: 'png',
                    pixelRatio: 2,
                    backgroundColor: '#ffffff',
                });
                const safeContrato = this.sanitizeFilePart(this.contrato);
                const safeComp = this.sanitizeFilePart(this.competencia || 'competencia');
                const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                const name = `pgu_${chartId}_${safeContrato}_${safeComp}_${stamp}.png`;
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                a.remove();
            } catch {
                window.alert('Não foi possível exportar o gráfico.');
            }
        },
        async exportElementPng(elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                window.alert('Elemento indisponível para exportação.');
                return;
            }
            try {
                const canvas = await html2canvas(element, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                });
                const url = canvas.toDataURL('image/png');
                const safeContrato = this.sanitizeFilePart(this.contrato);
                const safeComp = this.sanitizeFilePart(this.competencia || 'competencia');
                const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                const name = `pgu_${elementId}_${safeContrato}_${safeComp}_${stamp}.png`;
                const a = document.createElement('a');
                a.href = url;
                a.download = name;
                document.body.appendChild(a);
                a.click();
                a.remove();
            } catch {
                window.alert('Não foi possível exportar este card.');
            }
        },
        baseChart(id) {
            const element = document.getElementById(id);
            if (!element || !window.echarts) return null;
            const ec = window.echarts;
            const existing = typeof ec.getInstanceByDom === 'function' ? ec.getInstanceByDom(element) : null;
            if (existing) {
                existing.dispose();
            }
            const chart = ec.init(element, null, { renderer: 'canvas' });
            this.charts[id] = chart;
            return chart;
        },
        statusColor(status) {
            return {
                critical: '#B91C1C',
                high: '#C2410C',
                warning: '#D97706',
                success: '#059669',
                neutral: '#94A3B8',
            }[status] || '#94A3B8';
        },
        formatPctPtBr(n) {
            const v = Number(n);
            if (Number.isNaN(v)) return '0,0';
            return v.toFixed(1).replace('.', ',');
        },
        /** Números do histograma (pt-BR), até 2 decimais quando necessário. */
        formatQtyPtBr(n) {
            const v = Number(n);
            if (Number.isNaN(v)) return '0';
            const rounded = Math.round(v * 100) / 100;
            if (Number.isInteger(rounded)) return String(rounded);
            return rounded.toFixed(2).replace('.', ',');
        },
        /** Contagem de funções com plural correto em pt-BR (1 função / N funções). */
        formatFuncoesCountLabel(n) {
            let k = Math.round(Number(n));
            if (Number.isNaN(k) || k < 0) k = 0;
            if (k === 1) return '1 função';
            return `${this.formatQtyPtBr(k)} funções`;
        },
        /** 0 = tranquilo, 100 = crítico — usado só para cor (Avanço inverte: baixo % = alto stress). */
        heatmapStress(axisName, raw) {
            const v = Math.min(100, Math.max(0, Number(raw)));
            if (axisName === 'Avanço') {
                return 100 - v;
            }
            return v;
        },
        /** Tripla [xIndex, yIndex, terceiro] — heatmap: terceiro é stress (visualMap) ou valor bruto conforme o data. */
        heatmapCellTriple(params) {
            const d = params.data;
            if (d != null && typeof d === 'object' && !Array.isArray(d) && Array.isArray(d.value)) {
                return d.value;
            }
            if (Array.isArray(d) && d.length >= 3) {
                return d;
            }
            if (Array.isArray(params.value) && params.value.length >= 3) {
                return params.value;
            }
            return null;
        },
        renderCharts() {
            if (!this.data) return;
            const run = (name, fn) => {
                try {
                    fn.call(this);
                } catch (e) {
                    console.error(`[PGU] gráfico ${name}:`, e);
                }
            };
            run('chartDonut', () => this.renderDonut());
            run('chartMaoDeObra', () => this.renderMaoDeObra());
            run('chartRanking', () => this.renderRanking());
            run('chartFuncoes100Donut', () => this.renderFuncoes100Donut());
            run('chartParetoIndiretas', () => this.renderPareto('chartParetoIndiretas', this.data.pareto_executivo_indiretas || []));
            run('chartParetoDiretas', () => this.renderPareto('chartParetoDiretas', this.data.pareto_executivo_diretas || []));
            run('chartTrend', () => this.renderTrend());
            run('chartHeatmap', () => this.renderHeatmap());
            run('chartTreemap', () => this.renderTreemap());
            run('chartClientePanorama', () => this.renderClientePanorama());
            run('chartClienteEvolucao', () => this.renderClienteEvolucao());
            run('chartClienteMapaDonut', () => this.renderClienteMapaDonut());
            run('chartClienteDestaquesLinha', () => this.renderClienteDestaquesLinha());
            run('chartClienteDestaquesDonut', () => this.renderClienteDestaquesDonut());
            run('chartClientePlanoSemiDonut', () => this.renderClientePlanoSemiDonut());
            run('chartClienteMaturidadeEtapas', () => this.renderClienteMaturidadeEtapas());
            run('chartClienteMaturidadeComparativo', () => this.renderClienteMaturidadeComparativo());
        },
        renderClienteMaturidadeEtapas() {
            const chart = this.baseChart('chartClienteMaturidadeEtapas');
            if (!chart) return;
            const etapas = this.clienteMaturidadeEtapas();
            const fmt = this;
            chart.setOption({
                backgroundColor: 'transparent',
                grid: { left: 34, right: 12, top: 20, bottom: 48 },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: '#fff',
                    borderColor: '#E5E7EB',
                    textStyle: { color: '#0f172a', fontSize: 12 },
                    formatter(params) {
                        const p0 = Array.isArray(params) ? params[0] : params;
                        const idx = p0?.dataIndex;
                        const e = etapas[idx];
                        if (!e) return '';
                        return `<div style="font-weight:700;margin-bottom:6px">${e.label}</div>`
                            + `<div>% sobre o total de vagas: <strong>${fmt.formatPctPtBr(e.pct)}%</strong></div>`
                            + `<div style="margin-top:4px;color:#64748b">Realizado / total: ${fmt.formatQtyPtBr(e.value)} / ${fmt.formatQtyPtBr(e.total)}</div>`;
                    },
                },
                xAxis: {
                    type: 'category',
                    data: etapas.map((e) => e.label),
                    axisLabel: { color: '#64748B', fontSize: 10, interval: 0, rotate: 28 },
                },
                yAxis: { type: 'value', min: 0, max: 100, axisLabel: { color: '#64748B', formatter: '{value}%' }, splitLine: { lineStyle: { color: '#EEF2F7' } } },
                series: [{
                    name: '% do total de vagas',
                    type: 'bar',
                    barWidth: '52%',
                    data: etapas.map((e) => e.pct),
                    itemStyle: {
                        color: (p) => etapas[p.dataIndex]?.color || '#6F1731',
                        borderRadius: [6, 6, 0, 0],
                    },
                    label: {
                        show: true,
                        position: 'top',
                        formatter: (p) => `${this.formatPctPtBr(p.value)}%`,
                        color: '#6F1731',
                        fontWeight: 700,
                    },
                }],
            });
        },
        renderClienteMaturidadeComparativo() {
            const chart = this.baseChart('chartClienteMaturidadeComparativo');
            if (!chart) return;
            const etapas = this.clienteMaturidadeEtapas();
            const labels = etapas.map((e) => e.label);
            const realizado = etapas.map((e) => e.pct);
            const targetFinal = Math.max(realizado[realizado.length - 1] || 0, this.clienteCicloResumo().esperadoHoje || 0);
            const step = (100 - targetFinal) / Math.max(1, labels.length - 1);
            const projecao = labels.map((_, idx) => Math.round((100 - (step * idx)) * 10) / 10);
            const nomeProj = `Projeção ${this.clienteCicloResumo().dataLimite}`;
            const fmt = this;
            chart.setOption({
                backgroundColor: 'transparent',
                grid: { left: 34, right: 16, top: 26, bottom: 44 },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: '#fff',
                    borderColor: '#E5E7EB',
                    textStyle: { color: '#0f172a', fontSize: 12 },
                    formatter(params) {
                        const rows = Array.isArray(params) ? params : [params];
                        const idx = rows[0]?.dataIndex ?? 0;
                        const e = etapas[idx];
                        let html = e ? `<div style="font-weight:700;margin-bottom:6px">${e.label}</div>` : '';
                        rows.forEach((item) => {
                            const v = Number(item.value ?? item.data ?? 0);
                            const isProj = String(item.seriesName || '').startsWith('Projeção');
                            if (!isProj && e) {
                                html += `${item.marker} <strong>${item.seriesName}</strong>: ${fmt.formatPctPtBr(v)}% do total de vagas<br/>`;
                                html += `<span style="color:#64748b;margin-left:1.2em">${fmt.formatQtyPtBr(e.value)} / ${fmt.formatQtyPtBr(e.total)} vagas</span><br/>`;
                            } else {
                                html += `${item.marker} <strong>${item.seriesName}</strong>: ${fmt.formatPctPtBr(v)}% (curva de referência no prazo)<br/>`;
                            }
                        });
                        return html;
                    },
                },
                legend: { bottom: 0, textStyle: { color: '#64748B', fontSize: 11 } },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: { color: '#64748B', fontSize: 10, interval: 0, rotate: 28 },
                },
                yAxis: { type: 'value', min: 0, max: 100, axisLabel: { color: '#64748B', formatter: '{value}%' }, splitLine: { lineStyle: { color: '#EEF2F7' } } },
                series: [
                    {
                        name: 'Realizado',
                        type: 'line',
                        data: realizado,
                        smooth: true,
                        symbolSize: 6,
                        lineStyle: { color: '#6F1731', width: 2.5 },
                        itemStyle: { color: '#6F1731' },
                    },
                    {
                        name: nomeProj,
                        type: 'line',
                        data: projecao,
                        smooth: true,
                        symbolSize: 5,
                        lineStyle: { color: '#D4A3B3', width: 2, type: 'dashed' },
                        itemStyle: { color: '#D4A3B3' },
                    },
                ],
            });
        },
        renderClientePanorama() {
            const chart = this.baseChart('chartClientePanorama');
            if (!chart) return;
            const p = this.clientePanorama();
            const fases = this.clienteFases();
            const totalFases = Math.max(1, Number(this.data?.summary?.total_functions || 0));
            const faseFinal = fases[fases.length - 1] || { name: 'Liberação', value: 0 };
            const pctFinal = (Number(faseFinal.value || 0) / totalFases) * 100;
            const valoresFases = fases.map((f) => Math.max(0, Math.min(totalFases, Number(f.value || 0))));
            const somaFases = valoresFases.reduce((acc, v) => acc + v, 0);
            const fatorReducao = somaFases > totalFases ? (totalFases / somaFases) : 1;
            const valoresDonut = valoresFases.map((v) => v * fatorReducao);
            const somaDonut = valoresDonut.reduce((acc, v) => acc + v, 0);
            const totalPendente = Math.max(0, totalFases - somaDonut);

            const donutData = fases.map((f, idx) => {
                const v = Math.max(0, Number(valoresDonut[idx] || 0));
                const pct = totalFases > 0 ? (v / totalFases) * 100 : 0;
                const temFatia = v > 0;
                return {
                    value: v,
                    name: f.name,
                    labelText: `${f.name}: ${this.formatPctPtBr(pct)}%`,
                    label: { show: temFatia },
                    labelLine: { show: temFatia },
                    emphasis: {
                        label: { show: temFatia },
                        labelLine: { show: temFatia },
                    },
                };
            });
            donutData.push({
                value: totalPendente,
                name: 'Total pendente',
                labelText: `Total pendente: ${this.formatPctPtBr((totalPendente / totalFases) * 100)}%`,
                label: { show: totalPendente > 0 },
                labelLine: { show: totalPendente > 0 },
                emphasis: {
                    label: { show: totalPendente > 0 },
                    labelLine: { show: totalPendente > 0 },
                },
                itemStyle: { color: '#B8BDC7' },
            });
            /** Largura mínima para o motor do pie não aplicar `constrainTextWidth` (reticências). ~6.8px/caractere em 11px bold. */
            const rotulosCompletos = donutData
                .filter((d) => Number(d.value || 0) > 0)
                .map((d) => `${d.name} (${this.formatPctPtBr((Number(d.value) / totalFases) * 100)}%)`);
            const maxRotuloLen = rotulosCompletos.reduce((m, s) => Math.max(m, s.length), 0);
            const labelWidthPx = Math.min(480, Math.max(200, Math.ceil(maxRotuloLen * 6.8) + 8));
            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (d) => {
                        const v = Number(d.value || 0);
                        const pct = totalFases > 0 ? (v / totalFases) * 100 : 0;
                        return `<strong>${d.name}</strong><br/>Candidatos: ${this.formatQtyPtBr(v)}<br/>Participação: ${this.formatPctPtBr(pct)}%`;
                    },
                },
                color: [...fases.map((f) => f.color), '#B8BDC7'],
                title: { show: false },
                graphic: [
                    {
                        type: 'text',
                        left: 'center',
                        top: '50%',
                        silent: true,
                        style: {
                            text: `{big|${this.formatPctPtBr(pctFinal)}%}\n{sub|${faseFinal.name}}`,
                            textAlign: 'center',
                            textVerticalAlign: 'middle',
                            rich: {
                                big: { fontSize: 50, fontWeight: 800, fill: '#6F1731', lineHeight: 56 },
                                sub: {
                                    fontSize: String(faseFinal.name || '').length > 10 ? 14 : 16,
                                    fontWeight: 600,
                                    fill: '#475569',
                                    lineHeight: 22,
                                    width: 220,
                                    align: 'center',
                                },
                            },
                        },
                    },
                ],
                legend: { show: false },
                series: [
                    {
                        name: 'Panorama',
                        type: 'pie',
                        /* Anel um pouco menor + linhas mais curtas: rótulos ficam mais perto do centro e não estouram a borda, mantendo centro em 50%. */
                        radius: ['40%', '56%'],
                        center: ['50%', '50%'],
                        avoidLabelOverlap: true,
                        minShowLabelAngle: 0.8,
                        label: {
                            show: true,
                            position: 'outside',
                            distance: 8,
                            distanceToLabelLine: 3,
                            bleedMargin: 28,
                            color: '#334155',
                            fontSize: 11,
                            fontWeight: 700,
                            overflow: 'none',
                            width: labelWidthPx,
                            formatter: (p) => {
                                const row = donutData[p.dataIndex];
                                if (!row || Number(row.value || 0) <= 0) return '';
                                const pct = this.formatPctPtBr((Number(row.value || 0) / totalFases) * 100);
                                return `${row.name} (${pct}%)`;
                            },
                        },
                        labelLayout: {
                            hideOverlap: true,
                            moveOverlap: 'shiftY',
                        },
                        labelLine: {
                            show: true,
                            length: 18,
                            length2: 14,
                            smooth: false,
                            lineStyle: { color: '#94A3B8', width: 1 },
                        },
                        emphasis: {
                            scale: false,
                            labelLine: { lineStyle: { width: 1.5, color: '#64748B' } },
                        },
                        itemStyle: {
                            borderColor: '#fff',
                            borderWidth: 5,
                            borderRadius: 12,
                        },
                        data: donutData,
                    },
                ],
            });
        },
        renderClienteEvolucao() {
            const chart = this.baseChart('chartClienteEvolucao');
            if (!chart) return;
            const series = this.clienteEvolucaoSeries();
            if (series.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem histórico para o período',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            const labels = series.map((s) => s.date);
            const consolidadas = series.map((s) => s.consolidadas);
            const emEvolucao = series.map((s) => s.emEvolucao);
            const indice = series.map((s) => s.indice);
            const totais = series.map((s) => s.consolidadas + s.emEvolucao);
            const maxTotal = Math.max(...totais, 1);
            const echarts = window.echarts;
            const barConsolidadas = echarts?.graphic?.LinearGradient
                ? new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#6F1731' },
                    { offset: 1, color: '#9B2C4A' },
                ])
                : '#6F1731';
            const barEvolucao = '#D1D5DB';

            chart.setOption({
                backgroundColor: 'transparent',
                animationDuration: 600,
                animationEasing: 'cubicOut',
                grid: { left: 56, right: 64, top: 44, bottom: 46 },
                legend: {
                    top: 0,
                    textStyle: { color: '#475569', fontSize: 12 },
                    data: ['Funções consolidadas', 'Funções em evolução', 'Índice de Evolução (%)'],
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const cons = params.find((p) => p.seriesName === 'Funções consolidadas');
                        const evol = params.find((p) => p.seriesName === 'Funções em evolução');
                        const line = params.find((p) => p.seriesName === 'Índice de Evolução (%)');
                        if (!cons || !evol || !line) return '';
                        const total = Number(cons.value || 0) + Number(evol.value || 0);
                        return `<strong>${cons.name}</strong><br/>Mapeadas: ${this.formatQtyPtBr(total)}<br/>Consolidadas: ${this.formatQtyPtBr(cons.value)}<br/>Em evolução: ${this.formatQtyPtBr(evol.value)}<br/>Índice: ${this.formatPctPtBr(line.value)}%`;
                    },
                },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: '#E2E8F0' } },
                    axisLabel: { color: '#334155', fontSize: 12 },
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Número de Funções',
                        min: 0,
                        max: Math.ceil(maxTotal * 1.25),
                        splitLine: { lineStyle: { color: '#EEF2F7' } },
                        axisLabel: { color: '#64748B' },
                    },
                    {
                        type: 'value',
                        name: 'Índice de Evolução (%)',
                        min: 0,
                        max: 100,
                        axisLabel: { color: '#64748B', formatter: '{value}%' },
                        splitLine: { show: false },
                    },
                ],
                series: [
                    {
                        name: 'Funções consolidadas',
                        type: 'bar',
                        stack: 'total',
                        barWidth: '48%',
                        data: consolidadas,
                        itemStyle: { color: barConsolidadas, borderRadius: [6, 6, 0, 0] },
                        label: {
                            show: true,
                            position: 'inside',
                            color: '#FFFFFF',
                            fontWeight: 800,
                            formatter: (p) => this.formatQtyPtBr(p.value),
                        },
                    },
                    {
                        name: 'Funções em evolução',
                        type: 'bar',
                        stack: 'total',
                        barWidth: '48%',
                        data: emEvolucao,
                        itemStyle: { color: barEvolucao, borderRadius: [6, 6, 0, 0] },
                        label: {
                            show: true,
                            position: 'inside',
                            color: '#374151',
                            fontSize: 11,
                            fontWeight: 700,
                            formatter: (p) => this.formatQtyPtBr(p.value),
                        },
                    },
                    {
                        name: 'Índice de Evolução (%)',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 8,
                        data: indice,
                        lineStyle: { color: '#6F1731', width: 2.5 },
                        itemStyle: { color: '#FFFFFF', borderColor: '#6F1731', borderWidth: 2 },
                        label: {
                            show: true,
                            position: 'top',
                            color: '#6F1731',
                            fontSize: 11,
                            fontWeight: 700,
                            formatter: (p) => `${this.formatPctPtBr(p.value)}%`,
                        },
                    },
                    {
                        name: 'Totais',
                        type: 'bar',
                        stack: 'total',
                        silent: true,
                        barWidth: '48%',
                        data: totais.map(() => 0),
                        itemStyle: { color: 'transparent' },
                        tooltip: { show: false },
                        label: {
                            show: true,
                            position: 'top',
                            color: '#111827',
                            fontWeight: 800,
                            formatter: (p) => this.formatQtyPtBr(totais[p.dataIndex] || 0),
                        },
                    },
                ],
            });
        },
        renderClienteCobertura() {
            const chart = this.baseChart('chartClienteCobertura');
            if (!chart) return;
            const frentes = this.clienteCoberturaFrentes();
            if (frentes.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem dados de cobertura no período',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            const categorias = frentes.map((f) => f.frente);
            const cobertura = frentes.map((f) => f.cobertura);
            const monitoradas = frentes.map((f) => f.monitoradas);

            chart.setOption({
                backgroundColor: 'transparent',
                animationDuration: 600,
                grid: { left: 120, right: 40, top: 44, bottom: 32 },
                legend: {
                    top: 0,
                    textStyle: { color: '#475569', fontSize: 12 },
                    data: ['Funções monitoradas', 'Cobertura'],
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const bar = params.find((p) => p.seriesName === 'Cobertura');
                        if (!bar) return '';
                        const idx = bar.dataIndex;
                        return `<strong>${categorias[idx]}</strong><br/>Funções monitoradas: ${this.formatQtyPtBr(monitoradas[idx] || 0)}<br/>Cobertura: ${this.formatPctPtBr(cobertura[idx] || 0)}%`;
                    },
                },
                xAxis: {
                    type: 'value',
                    min: 0,
                    max: 100,
                    axisLabel: { color: '#64748B', formatter: '{value}%' },
                    splitLine: { lineStyle: { color: '#EEF2F7' } },
                },
                yAxis: {
                    type: 'category',
                    data: categorias,
                    axisTick: { show: false },
                    axisLine: { show: false },
                    axisLabel: { color: '#1F2937', fontWeight: 600 },
                },
                series: [
                    {
                        name: 'Cobertura',
                        type: 'bar',
                        data: cobertura,
                        barWidth: 18,
                        itemStyle: {
                            color: '#6F1731',
                            borderRadius: [0, 8, 8, 0],
                        },
                        label: {
                            show: true,
                            position: 'right',
                            color: '#6F1731',
                            fontWeight: 800,
                            formatter: (p) => `${this.formatPctPtBr(p.value)}%`,
                        },
                    },
                    {
                        name: 'Funções monitoradas',
                        type: 'scatter',
                        symbolSize: 0,
                        data: monitoradas,
                    },
                ],
            });
        },
        renderClienteMapaDonut() {
            const chart = this.baseChart('chartClienteMapaDonut');
            if (!chart) return;
            const rows = this.clienteConsolidacaoRows();
            const nMonitoradas = rows.length;
            const faixas = this.clienteConsolidacaoFaixasDonut();
            if (nMonitoradas === 0) {
                chart.setOption({
                    backgroundColor: 'transparent',
                    title: { show: false },
                    legend: { show: false },
                    series: [],
                });
                return;
            }
            const fmt = this;
            // Só fatias com valor > 0: no ECharts, zeros na série distorcem proporções e cores do anel.
            const data = faixas
                .map((f) => ({
                    name: f.label,
                    value: f.value,
                    itemStyle: {
                        color: f.color,
                    },
                }))
                .filter((d) => d.value > 0);
            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 12 },
                    formatter: (p) => {
                        const slice = faixas.find((f) => f.label === p.name);
                        if (!slice) return '';
                        const pct = nMonitoradas > 0 ? (slice.value / nMonitoradas) * 100 : 0;
                        return `<div style="font-weight:700;margin-bottom:4px">${slice.label}</div>`
                            + `${fmt.formatFuncoesCountLabel(slice.value)} (${fmt.formatPctPtBr(pct)}% do total monitorado)`;
                    },
                },
                title: { show: false },
                legend: { show: false },
                series: [{
                    name: 'Faixas',
                    type: 'pie',
                    radius: ['66%', '82%'],
                    center: ['50%', '50%'],
                    clockwise: true,
                    startAngle: 90,
                    padAngle: 0.6,
                    label: { show: false },
                    itemStyle: {
                        borderColor: '#ffffff',
                        borderWidth: 1.5,
                    },
                    emphasis: {
                        scale: false,
                        itemStyle: {
                            shadowBlur: 6,
                            shadowColor: 'rgba(15, 23, 42, 0.18)',
                        },
                    },
                    data,
                }],
            });
        },
        renderClienteDestaquesLinha() {
            const chart = this.baseChart('chartClienteDestaquesLinha');
            if (!chart) return;
            const baseline = this.clienteDestaquesBaselineInicioCiclo();
            const resumo = this.clienteCicloResumo();
            const inicio = this.parseDateAny(this.competencia ? `${this.competencia}-01` : null)
                || this.parseDateAny(this.data?.summary?.cycle_start_date);
            const dataLimite = this.parseDateAny(this.dataLimite);
            const labels = [
                this.formatDateBR(inicio),
                this.formatDateBR(new Date()),
                this.formatDateBR(dataLimite),
            ];
            const vals = [
                Math.max(0, Math.min(100, Number(baseline.progresso || 0))),
                Math.max(0, Math.min(100, Number(resumo.progressoAtual || 0))),
                100,
            ];
            const ec = window.echarts;
            const grad = ec?.graphic?.LinearGradient
                ? new ec.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(22, 101, 52, 0.35)' },
                    { offset: 1, color: 'rgba(22, 101, 52, 0.02)' },
                ])
                : 'rgba(22, 101, 52, 0.2)';
            const vmax = Math.min(100, Math.max(60, Math.ceil(((Math.max(...vals) || 0)) / 10) * 10));
            chart.setOption({
                backgroundColor: 'transparent',
                grid: { left: 44, right: 16, top: 28, bottom: 40 },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 12 },
                    formatter: (params) => {
                        const p0 = Array.isArray(params) ? params[0] : params;
                        return `<strong>${p0.axisValue}</strong><br/>Progresso médio no período: ${this.formatPctPtBr(p0.value)}%`;
                    },
                },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: { color: '#64748B', fontSize: 10, rotate: 28 },
                    axisTick: { show: false },
                },
                yAxis: {
                    type: 'value',
                    min: 0,
                    max: vmax,
                    axisLabel: { color: '#64748B', formatter: '{value}%' },
                    splitLine: { lineStyle: { color: '#EEF2F7' } },
                },
                series: [{
                    name: 'Progresso do período',
                    type: 'line',
                    smooth: false,
                    symbolSize: 6,
                    data: vals,
                    lineStyle: { color: '#166534', width: 2.5 },
                    itemStyle: { color: '#166534' },
                    areaStyle: { color: grad },
                    label: {
                        show: true,
                        position: 'top',
                        color: '#166534',
                        fontWeight: 700,
                        formatter: (p) => `${this.formatPctPtBr(p.value)}%`,
                    },
                }],
            });
        },
        renderClienteDestaquesDonut() {
            const chart = this.baseChart('chartClienteDestaquesDonut');
            if (!chart) return;
            const slices = this.clienteDestaquesContribuicaoEtapas().filter((s) => s.value > 0);
            if (slices.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem etapas com volume',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 14, fontWeight: 600 },
                    },
                    series: [],
                });
                return;
            }
            const data = slices.map((s) => ({
                name: s.label,
                value: s.value,
                itemStyle: { color: s.color },
            }));
            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 12 },
                    formatter: (p) => {
                        const sl = slices.find((x) => x.label === p.name);
                        if (!sl) return '';
                        return `${sl.label}<br/>+${this.formatPctPtBr(sl.pp)} p.p. · ${this.formatPctPtBr(sl.pctShare)}% do avanço atribuído`;
                    },
                },
                title: { show: false },
                legend: { show: false },
                series: [{
                    type: 'pie',
                    radius: ['58%', '78%'],
                    center: ['50%', '50%'],
                    label: { show: false },
                    itemStyle: { borderColor: '#fff', borderWidth: 2 },
                    data,
                }],
            });
        },
        renderClientePlanoSemiDonut() {
            const chart = this.baseChart('chartClientePlanoSemiDonut');
            if (!chart) return;
            const p = Math.max(0, Math.min(100, Number(this.clienteProgressoConsolidadoCicloPct())));
            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: { show: false },
                series: [{
                    name: 'Progresso',
                    type: 'pie',
                    radius: ['72%', '96%'],
                    center: ['50%', '90%'],
                    startAngle: 180,
                    clockwise: true,
                    label: { show: false },
                    silent: true,
                    itemStyle: { borderColor: '#fff', borderWidth: 0 },
                    data: [
                        { value: p, itemStyle: { color: '#7A1632' } },
                        { value: 100 - p, itemStyle: { color: '#ECEFF3' } },
                        { value: 100, itemStyle: { color: 'transparent' }, tooltip: { show: false } },
                    ],
                }],
            });
        },
        renderClienteCicloEvolucao() {
            const chart = this.baseChart('chartClienteCicloEvolucao');
            if (!chart) return;
            const resumo = this.clienteCicloResumo();
            const start = this.parseDateAny(this.data?.summary?.cycle_start_date) || this.parseDateAny(this.competencia ? `${this.competencia}-01` : null) || new Date();
            const end = this.parseDateAny(this.dataLimite) || new Date(start.getFullYear(), start.getMonth(), start.getDate() + 30);
            const today = new Date();
            const startDay = new Date(start.getFullYear(), start.getMonth(), start.getDate());
            const endDay = new Date(end.getFullYear(), end.getMonth(), end.getDate());
            const todayDay = new Date(today.getFullYear(), today.getMonth(), today.getDate());

            const ticks = [];
            const totalMs = Math.max(1, endDay.getTime() - startDay.getTime());
            const steps = 8;
            for (let i = 0; i <= steps; i++) {
                const d = new Date(startDay.getTime() + (totalMs * i) / steps);
                ticks.push(new Date(d.getFullYear(), d.getMonth(), d.getDate()));
            }
            ticks.push(todayDay);
            const uniqTicks = Array.from(new Map(ticks.map((d) => [`${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`, d])).values())
                .sort((a, b) => a.getTime() - b.getTime());

            const labels = uniqTicks.map((d) => `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}`);
            const realSeries = labels.map(() => null);
            const projSeries = labels.map(() => null);

            const idxByKey = new Map(uniqTicks.map((d, i) => [`${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`, i]));
            const keyOf = (d) => `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
            const idxStart = idxByKey.get(keyOf(startDay)) ?? 0;
            const idxToday = idxByKey.get(keyOf(todayDay)) ?? Math.floor(labels.length / 2);
            const idxEnd = idxByKey.get(keyOf(endDay)) ?? (labels.length - 1);

            // Ponto real de partida do ciclo.
            realSeries[idxStart] = 0;

            // Pontos reais históricos do sistema (série mensal real).
            const trend = Array.isArray(this.data?.trend) ? this.data.trend : [];
            trend.forEach((p) => {
                const ds = String(p?.date || '');
                const m = ds.match(/^(\d{2})\/(\d{4})$/);
                if (!m) return;
                const d = new Date(Number(m[2]), Number(m[1]) - 1, 1);
                const key = keyOf(d);
                const idx = idxByKey.get(key);
                if (idx == null) return;
                realSeries[idx] = Math.max(0, Math.min(100, Number(p?.progress || 0)));
            });

            // Ponto real atual (sempre ancorado no valor real do card).
            const progressoAtual = Math.max(0, Math.min(100, Number(resumo.progressoAtual || 0)));
            realSeries[idxToday] = progressoAtual;

            // Projeção apenas do hoje até a data limite.
            projSeries[idxToday] = progressoAtual;
            projSeries[idxEnd] = 100;

            chart.setOption({
                backgroundColor: 'transparent',
                grid: { left: 36, right: 16, top: 24, bottom: 54 },
                tooltip: { trigger: 'axis' },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: { color: '#64748B', fontSize: 10, interval: 0, hideOverlap: true },
                    axisTick: { show: false },
                },
                yAxis: { type: 'value', min: 0, max: 100, axisLabel: { color: '#64748B', formatter: '{value}%' }, splitLine: { lineStyle: { color: '#EEF2F7' } } },
                legend: { bottom: 0, textStyle: { color: '#64748B', fontSize: 11 }, itemWidth: 20 },
                series: [
                    {
                        name: 'Progresso real',
                        type: 'line',
                        connectNulls: true,
                        data: realSeries,
                        smooth: false,
                        symbolSize: 6,
                        lineStyle: { color: '#166534', width: 2.5 },
                        itemStyle: { color: '#166534' },
                        label: {
                            show: true,
                            formatter: (p) => (p.dataIndex === idxToday ? `${this.formatPctPtBr(p.value)}%` : ''),
                            color: '#166534',
                            fontWeight: 700,
                            backgroundColor: '#E9F9EE',
                            borderRadius: 4,
                            padding: [2, 6],
                        },
                    },
                    {
                        name: 'Projeção',
                        type: 'line',
                        connectNulls: true,
                        data: projSeries,
                        smooth: false,
                        symbolSize: 4,
                        lineStyle: { color: '#86EFAC', width: 2, type: 'dashed' },
                        itemStyle: { color: '#86EFAC' },
                        areaStyle: { color: 'rgba(134, 239, 172, 0.10)' },
                        label: {
                            show: true,
                            formatter: (p) => (p.dataIndex === idxEnd ? '100%' : ''),
                            color: '#166534',
                            fontWeight: 700,
                            backgroundColor: '#DCFCE7',
                            borderRadius: 4,
                            padding: [2, 6],
                        },
                    },
                ],
            });
        },
        renderDonut() {
            const chart = this.baseChart('chartDonut');
            if (!chart) return;
            const d = this.data.donut_avanco || {};
            const avanco = Math.min(Math.max(Number(d.avanco ?? d.overall ?? 0), 0), 100);
            const pendente = Math.min(Math.max(Number(d.pendente ?? 100 - avanco), 0), 100);

            const pctF2 = this.formatPctPtBr(avanco);
            const pctF1 = this.formatPctPtBr(pendente);
            const legF2 = `Cobertura · ${pctF2}%`;
            const legF1 = `Falta cobrir · ${pctF1}%`;

            chart.setOption({
                backgroundColor: 'transparent',
                color: ['#059669', '#CBD5E1'],
                title: {
                    text: `${pctF2}%`,
                    subtext: 'Média: mobilização (Pré)\nsobre necessidade (PGU)',
                    left: '50%',
                    top: '44%',
                    textAlign: 'center',
                    textVerticalAlign: 'middle',
                    textStyle: {
                        color: '#0F172A',
                        fontSize: 28,
                        fontWeight: 700,
                    },
                    subtextStyle: {
                        color: '#64748B',
                        fontSize: 13,
                        fontWeight: 500,
                        lineHeight: 18,
                    },
                },
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (p) => {
                        if (p.dataIndex === 0) {
                            return `<strong>Cobertura da PGU</strong><br/>${pctF2}% em média<br/><span style="opacity:.85">Pré-PGU (mobilizado) em relação à necessidade PGU por função.</span>`;
                        }
                        return `<strong>Falta cobrir</strong><br/>${pctF1}% em média<br/><span style="opacity:.85">Quanto falta, em média, para Pré atingir a PGU.</span>`;
                    },
                },
                legend: {
                    type: 'plain',
                    orient: 'horizontal',
                    left: 'center',
                    bottom: 14,
                    itemGap: 28,
                    itemWidth: 10,
                    itemHeight: 10,
                    icon: 'roundRect',
                    textStyle: {
                        color: '#64748B',
                        fontSize: 12,
                        rich: {},
                    },
                    data: [legF2, legF1],
                },
                series: [
                    {
                        name: 'Cobertura PGU',
                        type: 'pie',
                        radius: ['50%', '70%'],
                        center: ['50%', '44%'],
                        avoidLabelOverlap: true,
                        itemStyle: {
                            borderRadius: 8,
                            borderColor: '#FFFFFF',
                            borderWidth: 3,
                        },
                        label: { show: false },
                        data: [
                            { value: avanco, name: legF2 },
                            { value: pendente, name: legF1 },
                        ],
                    },
                ],
            });
        },
        renderMaoDeObra() {
            const chart = this.baseChart('chartMaoDeObra');
            if (!chart) return;
            const fases = this.data.fase_atual || [];
            if (fases.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem dados de fases',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            const ordered = [...fases].map((f, idx) => ({
                name: f.fase || `Fase ${idx + 1}`,
                value: Math.max(0, Number(f.valor ?? 0)),
            }));
            const totalEntrada = Math.max(1, Number(ordered[0]?.value ?? 1));
            const categories = ordered.map((f) => f.name);
            const values = ordered.map((f) => f.value);
            const percents = ordered.map((f) => Math.max(0, Math.min(100, (f.value / totalEntrada) * 100)));
            const maxValue = Math.max(...values, 1);
            const echarts = window.echarts;
            const barGradient = echarts?.graphic?.LinearGradient
                ? new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: '#0EA5E9' },
                    { offset: 1, color: '#0369A1' },
                ])
                : '#0284C7';

            chart.setOption({
                backgroundColor: 'transparent',
                animationDuration: 600,
                animationEasing: 'cubicOut',
                grid: { left: 48, right: 24, top: 42, bottom: 62 },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    padding: [12, 14],
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const bar = params.find((p) => p.seriesName === 'Candidatos');
                        const line = params.find((p) => p.seriesName === 'Avanço acumulado');
                        if (!bar) return '';
                        const idx = bar.dataIndex;
                        const acumulado = line ? Number(line.value || 0) : (percents[idx] ?? 0);
                        const prev = idx > 0 ? values[idx - 1] : values[0];
                        const etapa = prev > 0 ? (values[idx] / prev) * 100 : 0;
                        return `<strong>${bar.name}</strong><br/>Candidatos: ${this.formatQtyPtBr(bar.value)}<br/>Avanço acumulado: ${this.formatPctPtBr(acumulado)}%<br/>Conversão da etapa: ${this.formatPctPtBr(etapa)}%`;
                    },
                },
                legend: {
                    top: 4,
                    textStyle: { color: '#64748B', fontSize: 12 },
                    data: ['Candidatos', 'Avanço acumulado'],
                },
                xAxis: {
                    type: 'category',
                    data: categories,
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: '#E2E8F0' } },
                    axisLabel: {
                        color: '#334155',
                        fontSize: 12,
                        interval: 0,
                    },
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Candidatos',
                        min: 0,
                        max: Math.ceil(maxValue * 1.15),
                        axisLabel: { color: '#64748B' },
                        splitLine: { lineStyle: { color: '#EEF2F7' } },
                    },
                    {
                        type: 'value',
                        name: '',
                        min: 0,
                        max: 100,
                        axisLabel: { show: false },
                        axisTick: { show: false },
                        axisLine: { show: false },
                        splitLine: { show: false },
                    },
                ],
                series: [
                    {
                        name: 'Candidatos',
                        type: 'bar',
                        barWidth: '42%',
                        data: values,
                        itemStyle: {
                            color: barGradient,
                            borderRadius: [8, 8, 0, 0],
                        },
                        label: {
                            show: true,
                            position: 'top',
                            color: '#0F172A',
                            fontWeight: 700,
                            fontSize: 12,
                            formatter: (p) => this.formatQtyPtBr(p.value),
                        },
                    },
                    {
                        name: 'Avanço acumulado',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 7,
                        data: percents,
                        lineStyle: { color: '#2563EB', width: 3 },
                        itemStyle: { color: '#2563EB' },
                        label: {
                            show: true,
                            position: 'top',
                            color: '#1D4ED8',
                            fontSize: 11,
                            fontWeight: 700,
                            formatter: (p) => `${this.formatPctPtBr(p.value)}%`,
                        },
                    },
                ],
            });
        },
        renderRanking() {
            const chart = this.baseChart('chartRanking');
            if (!chart) return;
            const items = this.data.ranking_executivo || [];
            if (items.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem pendências no recorte',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            const maxValue = Math.max(...items.map((item) => item.pending), 1);

            chart.setOption({
                backgroundColor: 'transparent',
                grid: {
                    top: 16,
                    left: 16,
                    right: 56,
                    bottom: 16,
                    containLabel: true,
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    padding: [12, 14],
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const p = params[0];
                        const item = items[p.dataIndex];
                        if (!item) return '';
                        const cod = item.codigo ? `Código: ${item.codigo}<br/>` : '';
                        const tipo = item.tipo_pendencia || 'falta_mobilizar';
                        let nota = '<span style="opacity:.85;font-size:12px">Pendência = PGU − Pré (vagas).</span>';
                        if (tipo === 'pgu_nao_informado') {
                            nota = '<span style="opacity:.85;font-size:12px">Pré mobilizado sem PGU informado (prioridade para preencher meta).</span>';
                        } else if (tipo === 'agregado') {
                            nota = '<span style="opacity:.85;font-size:12px">Soma das demais funções (ranking executivo).</span>';
                        }
                        return `<strong>${item.funcao}</strong><br/>${cod}Volume no gráfico: ${item.pending}<br/>${nota}`;
                    },
                },
                xAxis: {
                    type: 'value',
                    max: Math.ceil(maxValue * 1.12),
                    splitLine: { show: false },
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: { show: false },
                },
                yAxis: {
                    type: 'category',
                    inverse: true,
                    data: items.map((item) => item.funcao),
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: {
                        color: '#0F172A',
                        fontSize: 15,
                        fontWeight: 600,
                        margin: 16,
                        formatter: (value, index) => {
                            const item = items[index];
                            if (!item) return value;
                            if (!item.codigo) {
                                return `{name|${value}}`;
                            }
                            return `{name|${value}}\n{code|Código ${item.codigo} · ${item.status_label || '—'}}`;
                        },
                        rich: {
                            name: {
                                color: '#0F172A',
                                fontSize: 15,
                                fontWeight: 700,
                                lineHeight: 22,
                            },
                            code: {
                                color: '#64748B',
                                fontSize: 12,
                                fontWeight: 500,
                                lineHeight: 18,
                            },
                        },
                    },
                },
                series: [
                    {
                        name: 'Pendências',
                        type: 'bar',
                        data: items.map((item) => ({
                            value: item.pending,
                            itemStyle: {
                                color: this.statusColor(item.status),
                                borderRadius: [0, 999, 999, 0],
                            },
                        })),
                        barWidth: 18,
                        barCategoryGap: '48%',
                        label: {
                            show: true,
                            position: 'right',
                            distance: 10,
                            color: '#0F172A',
                            fontSize: 16,
                            fontWeight: 800,
                            formatter: '{c}',
                        },
                        emphasis: { focus: 'series' },
                    },
                ],
            });
        },
        renderFuncoes100Donut() {
            const chart = this.baseChart('chartFuncoes100Donut');
            if (!chart) return;
            const items = this.data.funcoes_pgu_100 || [];
            const summary = this.data.summary || {};
            const n100 = Math.round(items.reduce((acc, row) => acc + Number(row?.completed || 0), 0));
            const total = Number(summary.total_functions ?? 0);
            const nDemais = Math.max(0, total - n100);

            if (total === 0) {
                chart.setOption({
                    backgroundColor: 'transparent',
                    title: {
                        text: 'Sem vagas no recorte',
                        subtext: 'Não há vagas monitoradas para esta competência.',
                        left: 'center',
                        top: 'middle',
                        textAlign: 'center',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                        subtextStyle: { color: '#94A3B8', fontSize: 13, lineHeight: 20 },
                    },
                    graphic: [],
                });
                return;
            }

            const pctIntegral = total > 0 ? Math.round((n100 / total) * 1000) / 10 : 0;
            const legIntegral = `Integral · ${n100}`;
            const legDemais = `Outras · ${nDemais}`;
            const fontStack = 'Instrument Sans, ui-sans-serif, system-ui, sans-serif';
            const donutCx = '50%';
            const donutCy = '42%';

            chart.setOption({
                backgroundColor: 'transparent',
                color: ['#0D9488', '#E2E8F0'],
                title: { show: false },
                graphic: [
                    {
                        type: 'text',
                        left: 'center',
                        top: donutCy,
                        silent: true,
                        z: 10,
                        style: {
                            text: `{main|${n100}}\n{sub|Vagas concluídas}\n{ratio|${n100}\u2009/\u2009${total}}`,
                            textAlign: 'center',
                            textVerticalAlign: 'middle',
                            rich: {
                                main: {
                                    fontSize: 38,
                                    fontWeight: 700,
                                    color: '#0F172A',
                                    fontFamily: fontStack,
                                    lineHeight: 44,
                                    align: 'center',
                                },
                                sub: {
                                    fontSize: 12,
                                    fontWeight: 600,
                                    color: '#64748B',
                                    fontFamily: fontStack,
                                    lineHeight: 20,
                                    align: 'center',
                                },
                                ratio: {
                                    fontSize: 16,
                                    fontWeight: 600,
                                    color: '#334155',
                                    fontFamily: fontStack,
                                    lineHeight: 24,
                                    align: 'center',
                                    fontVariantNumeric: 'tabular-nums',
                                },
                            },
                        },
                    },
                ],
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (p) => {
                        if (p.dataIndex === 0) {
                            return `<strong>100% cobertura</strong><br/>${n100} vaga(s) — ${this.formatPctPtBr(pctIntegral)}% do total`;
                        }
                        return `<strong>Demais vagas</strong><br/>${nDemais} ainda não concluídas`;
                    },
                },
                legend: {
                    orient: 'horizontal',
                    left: 'center',
                    bottom: 8,
                    itemGap: 32,
                    itemWidth: 14,
                    itemHeight: 14,
                    icon: 'roundRect',
                    textStyle: {
                        color: '#475569',
                        fontSize: 13,
                        fontWeight: 500,
                        fontFamily: fontStack,
                    },
                    data: [legIntegral, legDemais],
                },
                series: [
                    {
                        name: 'Cobertura',
                        type: 'pie',
                        radius: ['56%', '80%'],
                        center: [donutCx, donutCy],
                        avoidLabelOverlap: true,
                        itemStyle: {
                            borderRadius: 10,
                            borderColor: '#FFFFFF',
                            borderWidth: 4,
                            shadowBlur: 8,
                            shadowColor: 'rgba(15, 23, 42, 0.06)',
                            shadowOffsetY: 2,
                        },
                        emphasis: {
                            scale: false,
                            itemStyle: {
                                shadowBlur: 14,
                                shadowColor: 'rgba(15, 23, 42, 0.1)',
                            },
                        },
                        label: { show: false },
                        data: [
                            { value: n100, name: legIntegral },
                            { value: nDemais, name: legDemais },
                        ],
                    },
                ],
            });
        },
        renderPareto(chartId, items) {
            const chart = this.baseChart(chartId);
            if (!chart) return;
            if (items.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem dados para Pareto',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            const lastIdx = items.length - 1;

            chart.setOption({
                backgroundColor: 'transparent',
                grid: { top: 56, left: 56, right: 72, bottom: 88 },
                legend: {
                    top: 8,
                    left: 'center',
                    itemGap: 24,
                    textStyle: { color: '#64748B', fontSize: 13 },
                },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF' },
                },
                xAxis: {
                    type: 'category',
                    data: items.map((item) => item.funcao),
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: '#E2E8F0' } },
                    axisLabel: {
                        color: '#64748B',
                        fontSize: 12,
                        rotate: 22,
                        interval: 0,
                    },
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Pendência (vagas)',
                        nameTextStyle: { color: '#64748B' },
                        axisLabel: { color: '#64748B' },
                        splitLine: { lineStyle: { color: '#EEF2F7' } },
                    },
                    {
                        type: 'value',
                        min: 0,
                        max: 100,
                        name: '% acumulado',
                        nameTextStyle: { color: '#64748B' },
                        axisLabel: { color: '#64748B', formatter: '{value}%' },
                        splitLine: { show: false },
                    },
                ],
                series: [
                    {
                        name: 'Pendência (exec.)',
                        type: 'bar',
                        data: items.map((item) => item.pending),
                        barWidth: 28,
                        itemStyle: {
                            color: '#0F766E',
                            borderRadius: [10, 10, 0, 0],
                        },
                        label: {
                            show: true,
                            position: 'top',
                            distance: 8,
                            color: '#0F172A',
                            fontWeight: 700,
                            fontSize: 12,
                        },
                    },
                    {
                        name: 'Acumulado',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: true,
                        symbolSize: 8,
                        data: items.map((item) => item.accumulated),
                        lineStyle: { color: '#7E22CE', width: 3 },
                        itemStyle: { color: '#7E22CE' },
                        label: {
                            show: true,
                            position: 'top',
                            distance: 26,
                            color: '#6B21A8',
                            fontWeight: 700,
                            fontSize: 11,
                            formatter: (p) => (p.dataIndex === lastIdx ? '' : `${p.value}%`),
                            backgroundColor: 'rgba(255,255,255,0.96)',
                            borderColor: 'rgba(126, 34, 206, 0.35)',
                            borderWidth: 1,
                            borderRadius: 4,
                            padding: [4, 8],
                            shadowColor: 'rgba(15, 23, 42, 0.08)',
                            shadowBlur: 4,
                        },
                        markLine: {
                            symbol: 'none',
                            lineStyle: { color: '#B91C1C', type: 'dashed', width: 2 },
                            data: [
                                {
                                    yAxis: 80,
                                    label: {
                                        formatter: 'Pareto 80%',
                                        color: '#B91C1C',
                                        fontSize: 11,
                                        fontWeight: 600,
                                        // Centro do traço: evita colisão com ticks do eixo esquerdo (Pendências) e do direito (%).
                                        position: 'middle',
                                        distance: 18,
                                        backgroundColor: 'rgba(255,255,255,0.94)',
                                        borderRadius: 4,
                                        padding: [3, 8],
                                    },
                                },
                            ],
                        },
                    },
                ],
            });
        },
        renderTrend() {
            const chart = this.baseChart('chartTrend');
            if (!chart) return;
            const items = this.data.fase_trend || [];
            chart.setOption({
                backgroundColor: 'transparent',
                grid: { left: 48, right: 52, top: 40, bottom: 40 },
                tooltip: {
                    trigger: 'item',
                    confine: true,
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const mes = params.name;
                        const nome = params.seriesName;
                        const v = params.value;
                        return `<strong>${mes}</strong><br/>${nome}: ${this.formatQtyPtBr(v)} candidato(s)`;
                    },
                },
                legend: { top: 4, textStyle: { color: '#64748B' } },
                xAxis: {
                    type: 'category',
                    boundaryGap: false,
                    data: items.map((item) => item.date),
                    axisLabel: { color: '#64748B' },
                    axisTick: { show: false },
                    axisLine: { lineStyle: { color: '#E2E8F0' } },
                },
                yAxis: [
                    {
                        type: 'value',
                        name: 'Candidatos',
                        nameTextStyle: { color: '#64748B' },
                        axisLabel: { color: '#64748B' },
                        splitLine: { lineStyle: { color: '#EEF2F7' } },
                    },
                ],
                series: [
                    {
                        name: 'Vagas Preenchidas',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#6366F1' },
                        itemStyle: { color: '#6366F1' },
                        areaStyle: { color: 'rgba(99, 102, 241, 0.10)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.recrutamento ?? 0),
                    },
                    {
                        name: 'Exame Médico',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#0EA5E9' },
                        itemStyle: { color: '#0EA5E9' },
                        areaStyle: { color: 'rgba(14, 165, 233, 0.10)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.exame_medico ?? 0),
                    },
                    {
                        name: 'Treinamentos',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#10B981' },
                        itemStyle: { color: '#10B981' },
                        areaStyle: { color: 'rgba(16, 185, 129, 0.10)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.treinamentos ?? 0),
                    },
                    {
                        name: 'Assinatura documental',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#059669' },
                        itemStyle: { color: '#059669' },
                        areaStyle: { color: 'rgba(5, 150, 105, 0.10)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.assinatura_documental ?? 0),
                    },
                    {
                        name: 'Postagem SGC',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#F59E0B' },
                        itemStyle: { color: '#F59E0B' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.sgc ?? 0),
                    },
                    {
                        name: 'Liberação',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#EF4444' },
                        itemStyle: { color: '#EF4444' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.liberacao ?? 0),
                    },
                ],
            });
        },
        renderHeatmap() {
            const chart = this.baseChart('chartHeatmap');
            if (!chart) return;
            const heat = this.data.heatmap || [];
            const funcoes = [...new Set(heat.map((item) => item.funcao))];
            const axisOrder = ['Pendências', 'Avanço', 'Risco'];
            const axis = axisOrder.filter((a) => heat.some((h) => h.axis === a));
            /** Valores reais por célula (o 3º número da série é só “stress” para o visualMap). */
            const cellMeta = new Map();
            const values = heat
                .map((item) => {
                    const xi = axis.indexOf(item.axis);
                    const yi = funcoes.indexOf(item.funcao);
                    if (xi < 0 || yi < 0) {
                        return null;
                    }
                    const stress = this.heatmapStress(item.axis, item.value);
                    cellMeta.set(`${xi},${yi}`, {
                        axis: item.axis,
                        raw: item.value,
                    });
                    return [xi, yi, stress];
                })
                .filter((row) => row != null);

            const hints = {
                Pendências: 'Pendência exibida no ranking executivo (PGU − Pré, ou Pré quando PGU não informado). Até 100 na escala de calor.',
                Avanço: 'Quanto maior o % (Pré cobre PGU), melhor. Cor quente = avanço baixo (atenção).',
                Risco: 'Pontuação de risco pelo status da função. Cor quente = maior risco.',
            };

            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const triple = this.heatmapCellTriple(params);
                        if (!triple) return '';
                        const [xi, yi] = triple;
                        const meta = cellMeta.get(`${xi},${yi}`);
                        if (!meta) return '';
                        const fn = funcoes[yi];
                        const ax = meta.axis;
                        const val = meta.raw;
                        if (fn === undefined || ax === undefined) return '';
                        const vTxt = ax === 'Avanço' ? `${this.formatPctPtBr(val)}%` : String(val);
                        const hint = hints[ax] || '';
                        return `<strong>${fn}</strong><br/>${ax}: ${vTxt}<br/><span style="opacity:.85;font-size:12px">${hint}</span>`;
                    },
                },
                grid: { left: 168, right: 20, top: 28, bottom: 28 },
                xAxis: {
                    type: 'category',
                    data: axis,
                    axisLabel: { color: '#64748B', fontSize: 12 },
                    axisTick: { show: false },
                    axisLine: { show: false },
                },
                yAxis: {
                    type: 'category',
                    data: funcoes,
                    axisLabel: { color: '#0F172A', fontSize: 12, width: 150, overflow: 'break' },
                    axisTick: { show: false },
                    axisLine: { show: false },
                },
                visualMap: {
                    min: 0,
                    max: 100,
                    calculable: false,
                    show: false,
                    inRange: {
                        color: ['#D1FAE5', '#FEF3C7', '#FB923C', '#B91C1C'],
                    },
                },
                series: [
                    {
                        type: 'heatmap',
                        data: values,
                        label: {
                            show: true,
                            color: '#0F172A',
                            fontWeight: 600,
                            fontSize: 12,
                            formatter: (p) => {
                                const triple = this.heatmapCellTriple(p);
                                if (!triple) return '';
                                const [xi, yi] = triple;
                                const meta = cellMeta.get(`${xi},${yi}`);
                                if (!meta) return '';
                                const ax = meta.axis;
                                const v = meta.raw;
                                return ax === 'Avanço' ? `${this.formatPctPtBr(v)}%` : String(v);
                            },
                        },
                        itemStyle: { borderColor: '#FFFFFF', borderWidth: 3, borderRadius: 8 },
                    },
                ],
            });
        },
        renderTreemap() {
            const chart = this.baseChart('chartTreemap');
            if (!chart) return;
            const raw = this.data.treemap_pendencias || [];
            if (raw.length === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem dados para o mapa',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            chart.setOption({
                backgroundColor: 'transparent',
                tooltip: {
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF' },
                    formatter: (info) => `${info.name}<br/>Volume (ranking executivo): ${info.value}`,
                },
                series: [
                    {
                        type: 'treemap',
                        roam: false,
                        breadcrumb: { show: false },
                        nodeClick: false,
                        width: '100%',
                        height: '88%',
                        top: '6%',
                        label: {
                            show: true,
                            formatter: (p) => `${p.name}\n${p.value}`,
                            fontSize: 13,
                            fontWeight: 600,
                            color: '#0F172A',
                        },
                        upperLabel: { show: false },
                        itemStyle: {
                            borderColor: '#FFFFFF',
                            borderWidth: 2,
                            gapWidth: 2,
                            borderRadius: 6,
                        },
                        levels: [
                            {
                                itemStyle: {
                                    borderWidth: 2,
                                },
                                colorSaturation: [0.35, 0.65],
                                colorMappingBy: 'value',
                                color: ['#99F6E4', '#0F766E'],
                            },
                        ],
                        data: raw,
                    },
                ],
            });
        },
    };
};

/** Página Contrato › Apresentação: slide 1 renderizado no servidor; filtros recarregam a página com query string. */
window.pguApresentacaoShell = function () {
    return {
        contrato: '',
        competencia: '',
        dataLimite: '',
        exportUrl: '',
        exportandoPpt: false,
        exportProgressLabel: '',
        modoApresentacao: false,
        fullscreenRoot: null,
        touchStartX: null,
        touchStartY: null,
        abaApresentacao: 'capa',
        ordemAbasApresentacao: ['capa', 'geral', 'funcoes100', 'gargalos', 'concentracao', 'plano'],
        wheelCooldownMs: 300,
        lastWheelAt: 0,
        bloqueioNavegacaoAte: 0,
        initFromDataset() {
            const root = document.querySelector('[data-pgu-apresentacao]');
            if (!root) return;
            this.fullscreenRoot = root;
            this.contrato = root.dataset.contrato || '';
            this.competencia = root.dataset.competencia || '';
            this.dataLimite = root.dataset.dataLimite || '';
            this.exportUrl = root.dataset.exportUrl || '';
        },
        init() {
            this.initFromDataset();
            // Garante que a apresentação sempre abra na capa (slide 00).
            this.setAbaApresentacao('capa');
            // Evita avanço imediato por eventos de entrada/resíduo logo após o load.
            this.bloqueioNavegacaoAte = Date.now() + 900;
            this.bindSlideNavigation();
            this.bindFullscreenState();
            this.bindTouchSlideNavigation();
        },
        setAbaApresentacao(slug) {
            this.abaApresentacao = slug;
        },
        canHandleNavigationEvent(target) {
            if (!(target instanceof HTMLElement)) {
                return true;
            }
            return !target.closest('input, textarea, select, [contenteditable="true"]');
        },
        proximaAbaApresentacao() {
            const idxAtual = this.ordemAbasApresentacao.indexOf(this.abaApresentacao);
            const idxBase = idxAtual >= 0 ? idxAtual : 0;
            const proximoIdx = Math.min(this.ordemAbasApresentacao.length - 1, idxBase + 1);
            this.setAbaApresentacao(this.ordemAbasApresentacao[proximoIdx]);
        },
        abaApresentacaoAnterior() {
            const idxAtual = this.ordemAbasApresentacao.indexOf(this.abaApresentacao);
            const idxBase = idxAtual >= 0 ? idxAtual : 0;
            const anteriorIdx = Math.max(0, idxBase - 1);
            this.setAbaApresentacao(this.ordemAbasApresentacao[anteriorIdx]);
        },
        bindSlideNavigation() {
            window.addEventListener('wheel', (event) => {
                if (!this.canHandleNavigationEvent(event.target)) {
                    return;
                }
                if (Date.now() < this.bloqueioNavegacaoAte) {
                    return;
                }
                const now = Date.now();
                if (now - this.lastWheelAt < this.wheelCooldownMs) {
                    return;
                }
                if (event.deltaY < 0) {
                    // Regra solicitada: rolagem para cima avança slide.
                    this.proximaAbaApresentacao();
                    this.lastWheelAt = now;
                    return;
                }
                if (event.deltaY > 0) {
                    // Regra solicitada: rolagem para baixo volta slide.
                    this.abaApresentacaoAnterior();
                    this.lastWheelAt = now;
                }
            }, { passive: true });

            window.addEventListener('keydown', (event) => {
                if (!this.canHandleNavigationEvent(event.target)) {
                    return;
                }
                if (Date.now() < this.bloqueioNavegacaoAte) {
                    return;
                }
                if (event.key === 'Escape' && this.modoApresentacao) {
                    this.sairModoApresentacao();
                    event.preventDefault();
                    return;
                }
                if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
                    this.proximaAbaApresentacao();
                    event.preventDefault();
                    return;
                }
                if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
                    this.abaApresentacaoAnterior();
                    event.preventDefault();
                }
            });
        },
        bindTouchSlideNavigation() {
            const minSwipeDistance = 40;
            const maxOffAxisDistance = 120;

            window.addEventListener('touchstart', (event) => {
                if (!this.modoApresentacao) {
                    return;
                }
                if (Date.now() < this.bloqueioNavegacaoAte) {
                    return;
                }
                const touch = event.changedTouches?.[0];
                if (!touch) {
                    return;
                }
                this.touchStartX = touch.clientX;
                this.touchStartY = touch.clientY;
            }, { passive: true });

            window.addEventListener('touchend', (event) => {
                if (!this.modoApresentacao) {
                    return;
                }
                if (Date.now() < this.bloqueioNavegacaoAte) {
                    return;
                }
                const touch = event.changedTouches?.[0];
                if (!touch || this.touchStartX === null || this.touchStartY === null) {
                    this.touchStartX = null;
                    this.touchStartY = null;
                    return;
                }

                const deltaX = touch.clientX - this.touchStartX;
                const deltaY = touch.clientY - this.touchStartY;
                const absX = Math.abs(deltaX);
                const absY = Math.abs(deltaY);

                this.touchStartX = null;
                this.touchStartY = null;

                if (absX < minSwipeDistance && absY < minSwipeDistance) {
                    return;
                }

                // Swipe horizontal (padrão mobile): esquerda avança, direita volta.
                if (absX >= minSwipeDistance && absY <= maxOffAxisDistance) {
                    if (deltaX < 0) {
                        this.proximaAbaApresentacao();
                        return;
                    }
                    this.abaApresentacaoAnterior();
                    return;
                }

                // Swipe vertical: mantém mesma lógica já usada no scroll.
                if (absY >= minSwipeDistance && absX <= maxOffAxisDistance) {
                    if (deltaY < 0) {
                        this.proximaAbaApresentacao();
                        return;
                    }
                    this.abaApresentacaoAnterior();
                }
            }, { passive: true });
        },
        bindFullscreenState() {
            const syncState = () => {
                this.modoApresentacao = Boolean(document.fullscreenElement);
            };
            document.addEventListener('fullscreenchange', syncState);
            syncState();
        },
        isModoApresentacaoAtivo() {
            return this.modoApresentacao && Boolean(document.fullscreenElement);
        },
        async entrarModoApresentacao() {
            if (!this.fullscreenRoot || this.modoApresentacao) {
                return;
            }
            try {
                await this.fullscreenRoot.requestFullscreen();
            } catch (error) {
                const message = error instanceof Error
                    ? error.message
                    : 'Não foi possível abrir em tela cheia.';
                window.alert(message);
            }
        },
        async sairModoApresentacao() {
            if (!document.fullscreenElement) {
                this.modoApresentacao = false;
                return;
            }
            try {
                await document.exitFullscreen();
            } catch (error) {
                const message = error instanceof Error
                    ? error.message
                    : 'Não foi possível sair da tela cheia.';
                window.alert(message);
            }
        },
        async toggleModoApresentacao() {
            if (this.modoApresentacao) {
                await this.sairModoApresentacao();
                return;
            }
            await this.entrarModoApresentacao();
        },
        refresh() {
            const params = new URLSearchParams();
            if (this.contrato) params.set('contrato', this.contrato);
            if (this.competencia) params.set('competencia', this.competencia);
            if (this.dataLimite) params.set('data_limite_etapa_2', this.dataLimite);
            const q = params.toString();
            const path = window.location.pathname;
            window.location.href = q ? `${path}?${q}` : path;
        },
        async waitForUiRender() {
            await new Promise((resolve) => requestAnimationFrame(resolve));
            await new Promise((resolve) => setTimeout(resolve, 220));
            if (document.fonts?.ready) {
                await document.fonts.ready;
            }
        },
        getCurrentSlideElement() {
            const map = {
                capa: '.pgu0-apresentacao-embed .pgu0-slide-shell',
                geral: '.pgu-apresentacao-embed .pgu-slide-shell',
                funcoes100: '.pgu2-apresentacao-embed .pgu2-slide-shell',
                gargalos: '.pgu3-apresentacao-embed .pgu3-slide-shell',
                concentracao: '.pgu4-apresentacao-embed .pgu4-slide-shell',
                plano: '.pgu5-apresentacao-embed .pgu5-slide-shell',
            };
            const selector = map[this.abaApresentacao];
            if (!selector) {
                return null;
            }

            return document.querySelector(selector);
        },
        async captureCurrentSlidePng() {
            const element = this.getCurrentSlideElement();
            if (!element) {
                throw new Error(`Slide "${this.abaApresentacao}" não encontrado para captura.`);
            }

            const canvas = await html2canvas(element, {
                backgroundColor: '#ffffff',
                width: 1366,
                height: 768,
                scale: 2,
                useCORS: true,
                allowTaint: false,
                logging: false,
                imageTimeout: 15000,
                windowWidth: 1366,
                windowHeight: 768,
                onclone: (clonedDoc) => {
                    const shellSelectors = [
                        '.pgu0-slide-shell',
                        '.pgu-slide-shell',
                        '.pgu2-slide-shell',
                        '.pgu3-slide-shell',
                        '.pgu4-slide-shell',
                        '.pgu5-slide-shell',
                    ];
                    const scaleSelectors = [
                        '.pgu0-slide-scale',
                        '.pgu-slide-scale',
                        '.pgu2-slide-scale',
                        '.pgu3-slide-scale',
                        '.pgu4-slide-scale',
                        '.pgu5-slide-scale',
                    ];

                    clonedDoc.querySelectorAll(shellSelectors.join(',')).forEach((node) => {
                        node.style.width = '1366px';
                        node.style.height = '768px';
                        node.style.aspectRatio = 'auto';
                        node.style.maxWidth = 'none';
                        node.style.overflow = 'hidden';
                        node.style.containerType = 'inline-size';
                    });

                    clonedDoc.querySelectorAll(scaleSelectors.join(',')).forEach((node) => {
                        node.style.width = '1366px';
                        node.style.height = '768px';
                        node.style.transform = 'scale(1)';
                        node.style.transformOrigin = 'top left';
                    });
                },
            });

            return canvas.toDataURL('image/png');
        },
        buildPptFileName() {
            const safeContrato = String(this.contrato || 'contrato').replace(/[^\dA-Za-z_-]/g, '_');
            const safeComp = String(this.competencia || 'competencia').replace(/[^\dA-Za-z_-]/g, '_');

            return `pgu-visao-executiva-${safeContrato}-${safeComp}.pptx`;
        },
        async exportPpt() {
            if (this.exportandoPpt) return;
            this.exportandoPpt = true;
            try {
                const abaOriginal = this.abaApresentacao;
                const pptx = new PptxGenJS();
                pptx.layout = 'LAYOUT_WIDE';
                pptx.author = 'Omega286';
                pptx.subject = 'Apresentação PGU';
                pptx.title = 'PGU - Visão Executiva';
                pptx.company = 'Omega Service';

                for (let i = 0; i < this.ordemAbasApresentacao.length; i += 1) {
                    const aba = this.ordemAbasApresentacao[i];
                    this.abaApresentacao = aba;
                    this.exportProgressLabel = `Gerando slide ${i + 1}/${this.ordemAbasApresentacao.length}...`;
                    await this.waitForUiRender();

                    const dataUrl = await this.captureCurrentSlidePng();
                    const slide = pptx.addSlide();
                    slide.addImage({
                        data: dataUrl,
                        x: 0,
                        y: 0,
                        w: 13.333,
                        h: 7.5,
                    });
                }

                this.abaApresentacao = abaOriginal;
                this.exportProgressLabel = 'Finalizando arquivo...';
                await this.waitForUiRender();
                await pptx.writeFile({ fileName: this.buildPptFileName() });
                this.exportProgressLabel = '';
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Falha ao exportar PPT.';
                window.alert(message);
            } finally {
                this.exportProgressLabel = '';
                this.exportandoPpt = false;
            }
        },
    };
};

import html2canvas from 'html2canvas';
import PptxGenJS from 'pptxgenjs';

window.pguDashboard = function () {
    return {
        loading: true,
        error: null,
        data: null,
        charts: {},
        contrato: '',
        competencia: '',
        dataLimite: '',
        visaoAba: 'diretoria',
        clienteMenuOpen: false,
        clienteEvolucaoMenuOpen: false,
        clienteCoberturaMenuOpen: false,
        clienteMapaMenuOpen: false,
        setVisaoAba(tab) {
            this.visaoAba = tab;
            this.clienteMenuOpen = false;
            this.clienteEvolucaoMenuOpen = false;
            this.clienteCoberturaMenuOpen = false;
            this.clienteMapaMenuOpen = false;
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
            const consolidadas = Number(summary.completed_functions || 0);
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
            const colors = ['#6F1731', '#8B2C4A', '#A9445F', '#C3627A', '#D9879A'];
            return fases.map((f, idx) => ({
                name: f?.fase || `Fase ${idx + 1}`,
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
        clienteCoberturaGrupos() {
            const ranking = Array.isArray(this.data?.ranking_executivo) ? this.data.ranking_executivo : [];
            const baseRows = ranking
                .map((r) => {
                    const mapeadas = Math.max(0, Math.round(Number(r?.qty || 0)));
                    const monitoradas = mapeadas > 0 ? mapeadas : 0;
                    const cobertura = mapeadas > 0 ? (monitoradas / mapeadas) * 100 : 0;
                    return {
                        grupo: String(r?.funcao || 'Função'),
                        mapeadas,
                        monitoradas,
                        cobertura: Math.max(0, Math.min(100, Math.round(cobertura * 10) / 10)),
                        status: monitoradas >= mapeadas ? 'Monitorado' : 'Parcial',
                    };
                })
                .filter((r) => r.mapeadas > 0)
                .sort((a, b) => b.mapeadas - a.mapeadas);

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
            const ranking = Array.isArray(this.data?.ranking_executivo) ? this.data.ranking_executivo : [];
            return ranking
                .map((r) => {
                    const total = Math.max(0, Number(r?.qty || 0));
                    const consolidadas = Math.max(0, Number(r?.completed || 0));
                    const emEvolucao = Math.max(0, total - consolidadas);
                    const indice = total > 0 ? (consolidadas / total) * 100 : 0;
                    return {
                        funcao: String(r?.funcao || 'Função'),
                        total: Math.round(total),
                        consolidadas: Math.round(consolidadas),
                        emEvolucao: Math.round(emEvolucao),
                        indice: Math.round(indice * 10) / 10,
                    };
                })
                .filter((r) => r.total > 0)
                .sort((a, b) => b.total - a.total);
        },
        clienteConsolidacaoResumo() {
            const rows = this.clienteConsolidacaoRows();
            const mapeadas = rows.reduce((acc, r) => acc + r.total, 0);
            const consolidadas = rows.reduce((acc, r) => acc + r.consolidadas, 0);
            const emEvolucao = rows.reduce((acc, r) => acc + r.emEvolucao, 0);
            const indice = mapeadas > 0 ? (consolidadas / mapeadas) * 100 : 0;
            const delta = Number(this.data?.summary?.progress_delta || 0);
            return {
                mapeadas: Math.round(mapeadas),
                consolidadas: Math.round(consolidadas),
                emEvolucao: Math.round(emEvolucao),
                coberturaMonitorada: mapeadas > 0 ? 100 : 0,
                indice: Math.round(indice * 10) / 10,
                delta: Math.round(delta * 10) / 10,
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
            run('chartClienteCoberturaDonut', () => this.renderClienteCoberturaDonut());
            run('chartClienteMapaDonut', () => this.renderClienteMapaDonut());
        },
        renderClientePanorama() {
            const chart = this.baseChart('chartClientePanorama');
            if (!chart) return;
            const p = this.clientePanorama();
            const fases = this.clienteFases();
            const totalFases = Math.max(1, Number(this.data?.summary?.total_functions || 0));
            const faseFinal = fases[fases.length - 1] || { name: 'Liberação', value: 0 };
            const pctFinal = (Number(faseFinal.value || 0) / totalFases) * 100;
            const donutData = fases.map((f) => {
                const pct = (Number(f.value || 0) / totalFases) * 100;
                return {
                    value: f.value,
                    name: f.name,
                    labelText: `${f.name}: ${this.formatPctPtBr(pct)}%`,
                };
            });
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
                color: fases.map((f) => f.color),
                title: { show: false },
                graphic: [
                    {
                        type: 'text',
                        left: 'center',
                        top: '42%',
                        silent: true,
                        style: {
                            text: `{big|${this.formatPctPtBr(pctFinal)}%}\n{sub|${faseFinal.name}}`,
                            textAlign: 'center',
                            textVerticalAlign: 'middle',
                            rich: {
                                big: { fontSize: 50, fontWeight: 800, fill: '#6F1731', lineHeight: 56 },
                                sub: { fontSize: 16, fontWeight: 600, fill: '#475569', lineHeight: 24 },
                            },
                        },
                    },
                ],
                legend: {
                    show: true,
                    bottom: 6,
                    left: 'center',
                    itemWidth: 10,
                    itemHeight: 10,
                    textStyle: { color: '#475569', fontSize: 11 },
                    data: donutData.map((d) => d.labelText),
                },
                series: [
                    {
                        name: 'Panorama',
                        type: 'pie',
                        radius: ['46%', '64%'],
                        center: ['50%', '46%'],
                        avoidLabelOverlap: false,
                        minShowLabelAngle: 0,
                        label: {
                            show: true,
                            position: 'outside',
                            alignTo: 'edge',
                            edgeDistance: 8,
                            bleedMargin: 0,
                            width: 180,
                            overflow: 'break',
                            color: '#334155',
                            fontSize: 11,
                            fontWeight: 700,
                            formatter: (p) => {
                                const row = donutData[p.dataIndex];
                                if (!row) return `${p.name}`;
                                const pct = this.formatPctPtBr((Number(row.value || 0) / totalFases) * 100);
                                return `${row.name}\n${pct}%`;
                            },
                        },
                        labelLayout: {
                            hideOverlap: false,
                            moveOverlap: 'shiftY',
                        },
                        labelLine: {
                            show: true,
                            length: 16,
                            length2: 12,
                            smooth: false,
                            lineStyle: { color: '#94A3B8', width: 1 },
                        },
                        itemStyle: {
                            borderColor: '#fff',
                            borderWidth: 5,
                            borderRadius: 12,
                        },
                        emphasis: { scale: false },
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
        renderClienteCoberturaDonut() {
            const chart = this.baseChart('chartClienteCoberturaDonut');
            if (!chart) return;
            const resumo = this.clienteCoberturaResumo();
            const monitoradas = Math.max(0, Number(resumo.monitoradas || 0));
            const mapeadas = Math.max(0, Number(resumo.mapeadas || 0));
            const pendentes = Math.max(0, mapeadas - monitoradas);
            const cobertura = mapeadas > 0 ? (monitoradas / mapeadas) * 100 : 0;

            if (mapeadas === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem base mapeada',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }

            chart.setOption({
                backgroundColor: 'transparent',
                color: ['#166534', '#D1D5DB'],
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (p) => `${p.name}: ${this.formatQtyPtBr(p.value)}`,
                },
                title: {
                    text: `${this.formatPctPtBr(cobertura)}%`,
                    subtext: 'Funções\nMonitoradas',
                    left: 'center',
                    top: '42%',
                    textAlign: 'center',
                    textVerticalAlign: 'middle',
                    textStyle: { color: '#166534', fontSize: 44, fontWeight: 900 },
                    subtextStyle: { color: '#334155', fontSize: 24, fontWeight: 600, lineHeight: 30 },
                },
                legend: { show: false },
                series: [
                    {
                        name: 'Cobertura',
                        type: 'pie',
                        radius: ['62%', '82%'],
                        center: ['50%', '48%'],
                        label: { show: false },
                        itemStyle: { borderColor: '#FFFFFF', borderWidth: 4 },
                        data: [
                            { name: 'Monitoradas', value: monitoradas },
                            { name: 'Pendentes', value: pendentes },
                        ],
                    },
                ],
            });
        },
        renderClienteMapaDonut() {
            const chart = this.baseChart('chartClienteMapaDonut');
            if (!chart) return;
            const r = this.clienteConsolidacaoResumo();
            if (r.mapeadas === 0) {
                chart.setOption({
                    title: {
                        text: 'Sem dados de consolidação',
                        left: 'center',
                        top: 'middle',
                        textStyle: { color: '#64748B', fontSize: 16, fontWeight: 600 },
                    },
                });
                return;
            }
            chart.setOption({
                backgroundColor: 'transparent',
                color: ['#166534', '#EAB308'],
                tooltip: {
                    trigger: 'item',
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    textStyle: { color: '#FFFFFF', fontSize: 12 },
                    formatter: (p) => `${p.name}: ${this.formatQtyPtBr(p.value)} (${this.formatPctPtBr(p.percent)}%)`,
                },
                title: {
                    text: `${this.formatPctPtBr(r.indice)}%`,
                    subtext: 'Consolidada',
                    left: 'center',
                    top: '40%',
                    textAlign: 'center',
                    textStyle: { color: '#1F2937', fontSize: 30, fontWeight: 900 },
                    subtextStyle: { color: '#475569', fontSize: 14, fontWeight: 600 },
                },
                legend: {
                    orient: 'vertical',
                    bottom: 0,
                    left: 'left',
                    textStyle: { color: '#475569', fontSize: 11 },
                },
                series: [{
                    name: 'Distribuição',
                    type: 'pie',
                    radius: ['56%', '74%'],
                    center: ['52%', '40%'],
                    label: { show: false },
                    itemStyle: { borderColor: '#fff', borderWidth: 3 },
                    data: [
                        { name: `Consolidadas: ${r.consolidadas}`, value: r.consolidadas },
                        { name: `Em Evolução: ${r.emEvolucao}`, value: r.emEvolucao },
                    ],
                }],
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
                        name: 'Recrutamento',
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
                        name: 'Trein. + Assinatura',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 10,
                        triggerLineEvent: true,
                        lineStyle: { width: 3, color: '#10B981' },
                        itemStyle: { color: '#10B981' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.treinamentos_assinatura ?? 0),
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

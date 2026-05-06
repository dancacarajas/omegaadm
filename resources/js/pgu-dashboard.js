window.pguDashboard = function () {
    return {
        loading: true,
        error: null,
        data: null,
        charts: {},
        contrato: '',
        competencia: '',
        dataLimite: '',
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
            run('chartPareto', () => this.renderPareto());
            run('chartTrend', () => this.renderTrend());
            run('chartHeatmap', () => this.renderHeatmap());
            run('chartTreemap', () => this.renderTreemap());
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
            const m = this.data.mao_de_obra || {};
            const categories = ['Mobilização', 'Pré-PGU', 'PGU', 'Pós-PGU'];
            const keys = ['mobilizacao', 'pre_pgu', 'pgu', 'pos_pgu'];
            const values = keys.map((k) => Math.max(0, Number(m[k] ?? 0)));
            const maxVal = Math.max(...values, 1);
            const echarts = window.echarts;
            const barColor = (top, bottom) => {
                if (!echarts?.graphic?.LinearGradient) return top;
                return new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: top },
                    { offset: 1, color: bottom },
                ]);
            };
            const palette = [
                { top: '#94A3B8', bottom: '#64748B' },
                { top: '#2DD4BF', bottom: '#0D9488' },
                { top: '#0F766E', bottom: '#115E59' },
                { top: '#5EEAD4', bottom: '#14B8A6' },
            ];

            chart.setOption({
                backgroundColor: 'transparent',
                animationDuration: 550,
                animationEasing: 'cubicOut',
                grid: { left: 48, right: 24, top: 48, bottom: 56, containLabel: true },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow', shadowStyle: { color: 'rgba(15, 23, 42, 0.06)' } },
                    backgroundColor: '#0F172A',
                    borderWidth: 0,
                    padding: [12, 14],
                    textStyle: { color: '#FFFFFF', fontSize: 13 },
                    formatter: (params) => {
                        const p = params[0];
                        if (!p) return '';
                        const i = p.dataIndex;
                        const label = categories[i];
                        const val = values[i];
                        return `<strong>${label}</strong><br/>Volume: ${this.formatQtyPtBr(val)}`;
                    },
                },
                xAxis: {
                    type: 'category',
                    data: categories,
                    axisLine: { lineStyle: { color: '#E2E8F0' } },
                    axisTick: { show: false },
                    axisLabel: {
                        color: '#475569',
                        fontSize: 13,
                        fontWeight: 600,
                        interval: 0,
                        formatter: (name) => (name.length > 12 ? `${name.slice(0, 11)}…` : name),
                    },
                },
                yAxis: {
                    type: 'value',
                    max: Math.ceil(maxVal * 1.08),
                    min: 0,
                    splitLine: { lineStyle: { color: '#F1F5F9', type: 'dashed' } },
                    axisLine: { show: false },
                    axisTick: { show: false },
                    axisLabel: {
                        color: '#94A3B8',
                        fontSize: 12,
                        formatter: (v) =>
                            Number.isInteger(v) ? String(v) : String(v).replace('.', ','),
                    },
                },
                series: [
                    {
                        name: 'Volume',
                        type: 'bar',
                        barWidth: '42%',
                        barCategoryGap: '28%',
                        data: values.map((val, i) => ({
                            value: val,
                            itemStyle: {
                                color: barColor(palette[i].top, palette[i].bottom),
                                borderRadius: [10, 10, 4, 4],
                                shadowColor: 'rgba(15, 118, 110, 0.18)',
                                shadowBlur: 12,
                                shadowOffsetY: 4,
                            },
                        })),
                        label: {
                            show: true,
                            position: 'top',
                            distance: 8,
                            color: '#0F172A',
                            fontSize: 15,
                            fontWeight: 700,
                            formatter: (p) => this.formatQtyPtBr(p.value),
                        },
                        emphasis: {
                            focus: 'self',
                            itemStyle: {
                                shadowBlur: 20,
                                shadowColor: 'rgba(15, 118, 110, 0.35)',
                            },
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
            const n100 = items.length;
            const total = Number(summary.total_functions ?? 0);
            const nDemais = Math.max(0, total - n100);

            if (total === 0) {
                chart.setOption({
                    backgroundColor: 'transparent',
                    title: {
                        text: 'Sem funções no recorte',
                        subtext: 'Não há linhas de histograma para esta competência.',
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
                            text: `{main|${n100}}\n{sub|Pré cobre PGU}\n{ratio|${n100}\u2009/\u2009${total}}`,
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
                            return `<strong>100% cobertura</strong><br/>${n100} função(ões) — ${this.formatPctPtBr(pctIntegral)}% do total`;
                        }
                        return `<strong>Demais funções</strong><br/>${nDemais} com falta de mobilização ou PGU não informado`;
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
        renderPareto() {
            const chart = this.baseChart('chartPareto');
            if (!chart) return;
            const items = this.data.pareto_executivo || [];
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
            const items = this.data.trend || [];
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
                        if (nome === 'Avanço médio') {
                            return `<strong>${mes}</strong><br/>${nome}: ${this.formatPctPtBr(v)}%`;
                        }
                        return `<strong>${mes}</strong><br/>${nome}: ${this.formatQtyPtBr(v)} vagas`;
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
                        name: 'Vagas',
                        nameTextStyle: { color: '#64748B' },
                        axisLabel: { color: '#64748B' },
                        splitLine: { lineStyle: { color: '#EEF2F7' } },
                    },
                    {
                        type: 'value',
                        name: '% avanço médio',
                        min: 0,
                        max: 100,
                        nameTextStyle: { color: '#2563EB' },
                        axisLabel: { color: '#2563EB', formatter: '{value}%' },
                        splitLine: { show: false },
                    },
                ],
                series: [
                    {
                        name: 'Pendentes',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#D97706' },
                        itemStyle: { color: '#D97706' },
                        areaStyle: { color: 'rgba(217, 119, 6, 0.12)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.pending),
                    },
                    {
                        name: 'Coberto (min Pré, PGU)',
                        type: 'line',
                        yAxisIndex: 0,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 9,
                        triggerLineEvent: true,
                        lineStyle: { width: 2.5, color: '#059669' },
                        itemStyle: { color: '#059669' },
                        areaStyle: { color: 'rgba(5, 150, 105, 0.12)' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.completed),
                    },
                    {
                        name: 'Avanço médio',
                        type: 'line',
                        yAxisIndex: 1,
                        smooth: true,
                        showSymbol: true,
                        symbolSize: 10,
                        triggerLineEvent: true,
                        lineStyle: { width: 3, color: '#2563EB' },
                        itemStyle: { color: '#2563EB' },
                        emphasis: { focus: 'series' },
                        data: items.map((item) => item.progress),
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
        exportPpt() {
            if (!this.exportUrl || this.exportandoPpt) return;
            this.exportandoPpt = true;
            try {
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = this.exportUrl;
                form.style.display = 'none';

                const addInput = (name, value) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                };

                if (this.contrato) addInput('contrato', this.contrato);
                if (this.competencia) addInput('competencia', this.competencia);
                if (this.dataLimite) addInput('data_limite_etapa_2', this.dataLimite);

                document.body.appendChild(form);
                form.submit();
                form.remove();
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Falha ao exportar PPT.';
                window.alert(message);
            } finally {
                setTimeout(() => {
                    this.exportandoPpt = false;
                }, 1200);
            }
        },
    };
};

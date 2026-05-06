import './bootstrap';
import Alpine from 'alpinejs';
import * as echarts from 'echarts';
import { CountUp } from 'countup.js';
import { initAppLucideIcons } from './charts/icons.js';
import './charts/pgu-slide1-premium.js';
import Stepper from 'bs-stepper';
import ApexCharts from 'apexcharts';
import './pgu-dashboard';
import 'bs-stepper/dist/css/bs-stepper.min.css';

window.Alpine = Alpine;
window.echarts = echarts;
window.ApexCharts = ApexCharts;
window.CountUp = CountUp;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initAppLucideIcons();

    document.querySelectorAll('[data-apex-chart]').forEach((element) => {
        const configElement = document.querySelector(element.getAttribute('data-apex-chart'));

        if (!configElement) {
            return;
        }

        try {
            const options = JSON.parse(configElement.textContent);
            const chart = new ApexCharts(element, options);
            chart.render();
        } catch (error) {
            console.error('Erro ao renderizar gráfico ApexCharts', error);
        }
    });

    document.querySelectorAll('[data-menu-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const key = toggle.getAttribute('data-menu-toggle');
            const panel = document.querySelector(`[data-menu-panel="${key}"]`);
            const chevron = document.querySelector(`[data-menu-chevron="${key}"]`);

            if (!panel) {
                return;
            }

            panel.classList.toggle('hidden');
            chevron?.classList.toggle('rotate-180');
        });
    });

    const sidebar = document.querySelector('[data-app-sidebar]');
    const mobileNavBackdrop = document.querySelector('[data-mobile-nav-backdrop]');
    const mobileNavToggles = document.querySelectorAll('[data-mobile-nav-toggle]');

    const isDesktopNav = () => window.matchMedia('(min-width: 1024px)').matches;

    const setMobileNavOpen = (open) => {
        if (!sidebar) {
            return;
        }

        if (isDesktopNav()) {
            document.body.classList.remove('overflow-hidden');
            mobileNavBackdrop?.classList.add('opacity-0', 'pointer-events-none');
            mobileNavBackdrop?.classList.remove('opacity-100');
            mobileNavBackdrop?.setAttribute('aria-hidden', 'true');
            mobileNavToggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));

            return;
        }

        if (open) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            mobileNavBackdrop?.classList.remove('opacity-0', 'pointer-events-none');
            mobileNavBackdrop?.classList.add('opacity-100');
            mobileNavBackdrop?.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
            mobileNavToggles.forEach((t) => t.setAttribute('aria-expanded', 'true'));
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            mobileNavBackdrop?.classList.add('opacity-0', 'pointer-events-none');
            mobileNavBackdrop?.classList.remove('opacity-100');
            mobileNavBackdrop?.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            mobileNavToggles.forEach((t) => t.setAttribute('aria-expanded', 'false'));
        }
    };

    mobileNavToggles.forEach((btn) => {
        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            setMobileNavOpen(!expanded);
        });
    });

    document.querySelectorAll('[data-mobile-nav-close]').forEach((btn) => {
        btn.addEventListener('click', () => setMobileNavOpen(false));
    });

    mobileNavBackdrop?.addEventListener('click', () => setMobileNavOpen(false));

    sidebar?.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktopNav()) {
                setMobileNavOpen(false);
            }
        });
    });

    window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
        if (event.matches) {
            setMobileNavOpen(false);
        }
    });

    document.querySelectorAll('.js-stepper').forEach((element) => {
        element.stepper = new Stepper(element, {
            linear: false,
            animation: true,
        });

        const currentStepInput = document.querySelector('#current_step');
        const stepTriggers = Array.from(element.querySelectorAll('.step-trigger'));

        const syncCurrentStep = (stepId) => {
            if (!stepId) {
                return;
            }

            if (currentStepInput) {
                currentStepInput.value = stepId;
            }

            const url = new URL(window.location.href);
            url.searchParams.set('step', stepId);
            window.history.replaceState({}, '', url);
        };

        stepTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const target = trigger.getAttribute('aria-controls');

                element.querySelectorAll('.content').forEach((content) => {
                    content.classList.remove('step-visible');
                });

                if (target) {
                    element.querySelector(`#${target}`)?.classList.add('step-visible');
                    syncCurrentStep(target);
                }
            });
        });

        const initialStep = currentStepInput?.value || new URLSearchParams(window.location.search).get('step');
        const initialTrigger = initialStep ? element.querySelector(`#${initialStep}-trigger`) : null;

        if (initialTrigger instanceof HTMLElement) {
            initialTrigger.click();
        }
    });

    const currentStepInput = document.querySelector('#current_step');

    document.querySelectorAll('[data-next-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetStep = button.getAttribute('data-next-step');

            if (currentStepInput && targetStep) {
                currentStepInput.value = targetStep;
            }
        });
    });

    const svgPostagem = document.querySelector('[data-svg-postagem]');
    const vistoriaInicio = document.querySelector('[data-vistoria-previsao-inicio]');
    const vistoriaFim = document.querySelector('[data-vistoria-previsao-fim]');
    const vistoriaTexto = document.querySelector('[data-vistoria-sla-text]');

    const addDays = (dateValue, days) => {
        const [year, month, day] = dateValue.split('-').map(Number);
        const date = new Date(Date.UTC(year, month - 1, day));
        date.setUTCDate(date.getUTCDate() + days);

        return date.toISOString().slice(0, 10);
    };

    const formatDate = (dateValue) => {
        const [year, month, day] = dateValue.split('-');

        return `${day}/${month}/${year}`;
    };

    const updateVistoriaSla = () => {
        if (!svgPostagem || !vistoriaInicio || !vistoriaFim) {
            return;
        }

        if (!svgPostagem.value) {
            vistoriaInicio.value = '';
            vistoriaFim.value = '';

            if (vistoriaTexto) {
                vistoriaTexto.textContent = 'Informe a data de postagem no SVG para calcular a janela prevista.';
            }

            return;
        }

        const inicio = addDays(svgPostagem.value, 3);
        const fim = addDays(svgPostagem.value, 10);

        vistoriaInicio.value = inicio;
        vistoriaFim.value = fim;

        if (vistoriaTexto) {
            vistoriaTexto.textContent = `SLA previsto entre ${formatDate(inicio)} e ${formatDate(fim)}, considerando 3 a 10 dias apos a postagem no SVG.`;
        }
    };

    svgPostagem?.addEventListener('change', updateVistoriaSla);
    updateVistoriaSla();

    const finalizacaoItems = document.querySelectorAll('[data-finalizacao-item]');
    const finalizacaoStatus = document.querySelector('[data-finalizacao-status]');
    const finalizacaoBadge = document.querySelector('[data-finalizacao-badge]');
    const finalizacaoTitle = document.querySelector('[data-finalizacao-title]');
    const finalizacaoDescription = document.querySelector('[data-finalizacao-description]');
    const finalizacaoProgress = document.querySelector('[data-finalizacao-progress]');
    const finalizacaoSubmit = document.querySelector('[data-finalizacao-submit]');
    const progressBar = document.querySelector('[data-step-progress-bar]');
    const progressLabel = document.querySelector('[data-step-progress-label]');

    const getField = (name) => document.querySelector(`[name="${name}"]`);
    const hasValue = (name) => Boolean(getField(name)?.value?.trim());
    const hasFile = (name) => {
        const field = getField(name);

        return (field?.files?.length ?? 0) > 0 || field?.dataset.existingFile === '1';
    };
    const isChecked = (name) => Boolean(document.querySelector(`[name="${name}"][value="1"]`)?.checked);

    const autoCheckRules = {
        veiculo_solicitado: () => hasValue('finalidade'),
        periodo_definido: () => hasValue('data_inicio_atividade') && hasValue('data_fim_atividade'),
        inspecao_prevista: () => hasValue('data_liberacao_inspecao'),
        linha_confirmada: () => hasValue('contrato') && hasValue('linha_contratual'),
        criterios_conferidos: () => hasValue('criterio_tecnico'),
        anexo_validado: () => true,
        tag_crlv_conferido: () => hasFile('crlv'),
        tag_dados_completos: () => [
            'placa',
            'renavam',
            'marca',
            'modelo',
            'ano_fabricacao',
            'proprietario',
        ].every(hasValue),
        tag_evidencia_salva: () => hasValue('tag_data_solicitacao')
            && hasValue('tag_numero_protocolo')
            && hasFile('tag_evidencia'),
        subcontratacao_analise_inicial: () => hasValue('subcontratacao_data_analise'),
        subcontratacao_autorizacao_aprovada: () => hasValue('subcontratacao_data_autorizacao'),
        svg_documentacao_reunida: () => hasFile('crlv')
            && hasFile('subcontratacao_cartao_cnpj')
            && hasFile('subcontratacao_minuta')
            && hasFile('subcontratacao_contrato_social')
            && hasFile('subcontratacao_documento_veiculo'),
        svg_mobilizacao_postada: () => hasValue('svg_data_postagem')
            && hasValue('svg_protocolo')
            && hasFile('svg_evidencia'),
        svg_fluxo_acompanhado: () => hasValue('svg_data_postagem') && hasValue('svg_protocolo'),
        svg_pendencias_corrigidas: () => hasValue('svg_data_postagem') && hasValue('svg_protocolo'),
        vistoria_data_prevista: () => hasValue('vistoria_previsao_inicio') && hasValue('vistoria_previsao_fim'),
        vistoria_veiculo_disponivel: () => hasValue('vistoria_data_agendada'),
        vistoria_checklist_revisado: () => hasValue('vistoria_data_agendada'),
        vistoria_resultado_registrado: () => Boolean(getField('vistoria_resultado')?.value),
    };

    const workflowStepRules = [
        () => autoCheckRules.veiculo_solicitado()
            && autoCheckRules.periodo_definido()
            && autoCheckRules.inspecao_prevista()
            && autoCheckRules.linha_confirmada()
            && autoCheckRules.criterios_conferidos()
            && autoCheckRules.anexo_validado(),
        () => [
            'placa',
            'renavam',
            'tipo',
            'marca',
            'modelo',
            'ano_fabricacao',
            'ano_modelo',
            'cor',
            'proprietario',
            'fornecedor',
        ].every(hasValue) && hasFile('crlv'),
        () => autoCheckRules.tag_crlv_conferido()
            && autoCheckRules.tag_dados_completos()
            && autoCheckRules.tag_evidencia_salva(),
        () => autoCheckRules.subcontratacao_analise_inicial()
            && autoCheckRules.subcontratacao_autorizacao_aprovada()
            && hasFile('subcontratacao_cartao_cnpj')
            && hasFile('subcontratacao_minuta')
            && hasFile('subcontratacao_contrato_social')
            && hasFile('subcontratacao_documento_veiculo'),
        () => autoCheckRules.svg_documentacao_reunida()
            && autoCheckRules.svg_mobilizacao_postada()
            && autoCheckRules.svg_fluxo_acompanhado()
            && autoCheckRules.svg_pendencias_corrigidas(),
        () => autoCheckRules.vistoria_data_prevista()
            && autoCheckRules.vistoria_veiculo_disponivel()
            && autoCheckRules.vistoria_checklist_revisado()
            && autoCheckRules.vistoria_resultado_registrado(),
        () => Object.values(finalizacaoRules).every((rule) => rule.done()),
    ];

    const updateStepperProgress = () => {
        if (!progressBar || !progressLabel || !workflowStepRules.length) {
            return;
        }

        const completed = workflowStepRules.reduce((count, rule) => count + (rule() ? 1 : 0), 0);
        const total = workflowStepRules.length;
        const percent = Math.round((completed / total) * 100);

        progressBar.style.width = `${percent}%`;
        progressLabel.textContent = `${percent}% (${completed}/${total} steps)`;
    };

    const updateAutoChecks = () => {
        Object.entries(autoCheckRules).forEach(([key, rule]) => {
            const checkbox = document.querySelector(`[data-auto-check="${key}"]`);
            const card = document.querySelector(`[data-auto-check-card="${key}"]`);
            const done = Boolean(rule());

            if (checkbox) {
                checkbox.checked = done;
                checkbox.readOnly = true;
            }

            card?.classList.toggle('border-emerald-200', done);
            card?.classList.toggle('bg-emerald-50', done);
            card?.classList.toggle('border-zinc-200', !done);
            card?.classList.toggle('bg-white', !done);
        });
    };

    const finalizacaoRules = {
        solicitacao: {
            message: 'Complete periodo, contrato, linha, criterio tecnico e demanda.',
            done: () => [
                'data_inicio_atividade',
                'data_fim_atividade',
                'contrato',
                'linha_contratual',
                'criterio_tecnico',
                'finalidade',
            ].every(hasValue),
        },
        tag: {
            message: 'Informe a data, protocolo, evidencia da TAG e confirme os dados.',
            done: () => hasValue('tag_data_solicitacao')
                && hasValue('tag_numero_protocolo')
                && hasFile('tag_evidencia')
                && isChecked('tag_checklist_data[dados_completos]')
                && isChecked('tag_checklist_data[evidencia_salva]'),
        },
        subcontratacao: {
            message: 'Registre analise, autorizacao e documentos obrigatorios.',
            done: () => hasValue('subcontratacao_data_analise')
                && hasValue('subcontratacao_data_autorizacao')
                && hasFile('subcontratacao_cartao_cnpj')
                && hasFile('subcontratacao_minuta')
                && hasFile('subcontratacao_contrato_social')
                && hasFile('subcontratacao_documento_veiculo')
                && isChecked('subcontratacao_checklist_data[analise_inicial]')
                && isChecked('subcontratacao_checklist_data[autorizacao_aprovada]'),
        },
        svg: {
            message: 'Registre postagem no SVG, evidencia e fluxo Vale acompanhado.',
            done: () => hasValue('svg_data_postagem')
                && hasValue('svg_protocolo')
                && hasFile('svg_evidencia')
                && isChecked('svg_checklist_data[mobilizacao_postada]')
                && isChecked('svg_checklist_data[fluxo_acompanhado]'),
        },
        vistoria: {
            message: 'Agende a vistoria e marque resultado aprovado.',
            done: () => hasValue('vistoria_previsao_inicio')
                && hasValue('vistoria_previsao_fim')
                && hasValue('vistoria_data_agendada')
                && getField('vistoria_resultado')?.value === 'aprovado',
        },
    };

    const setFinalizacaoItemState = (item, done, message) => {
        const icon = item.querySelector('[data-finalizacao-icon]');
        const text = item.querySelector('[data-finalizacao-message]');

        item.classList.toggle('border-emerald-200', done);
        item.classList.toggle('bg-emerald-50', done);
        item.classList.toggle('border-zinc-200', !done);
        item.classList.toggle('bg-white', !done);
        icon?.classList.toggle('border-emerald-600', done);
        icon?.classList.toggle('bg-emerald-600', done);
        icon?.classList.toggle('border-zinc-300', !done);
        icon?.classList.toggle('bg-white', !done);

        if (text) {
            text.textContent = done ? 'Concluido automaticamente.' : message;
            text.classList.toggle('text-emerald-700', done);
            text.classList.toggle('text-brand-burgundy', !done);
        }
    };

    const updateFinalizacao = () => {
        if (!finalizacaoItems.length) {
            return;
        }

        let completed = 0;

        finalizacaoItems.forEach((item) => {
            const key = item.getAttribute('data-finalizacao-item');
            const rule = finalizacaoRules[key];
            const done = Boolean(rule?.done());

            if (done) {
                completed += 1;
            }

            setFinalizacaoItemState(item, done, rule?.message ?? 'Complete esta etapa.');
        });

        const total = finalizacaoItems.length;
        const finished = completed === total;

        if (finalizacaoStatus) {
            finalizacaoStatus.textContent = finished ? 'Finalizada' : 'Nao finalizada';
            finalizacaoStatus.className = finished
                ? 'inline-flex w-fit items-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-black text-white shadow-sm'
                : 'inline-flex w-fit items-center rounded-full bg-white px-4 py-2 text-xs font-black text-brand-gray shadow-sm';
        }

        if (finalizacaoBadge) {
            finalizacaoBadge.textContent = finished ? 'Liberado com evidencia' : 'Pendencias abertas';
            finalizacaoBadge.className = finished
                ? 'inline-flex w-fit items-center rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-xs font-black text-emerald-700'
                : 'inline-flex w-fit items-center rounded-full border border-zinc-200 bg-brand-gray-soft px-4 py-2 text-xs font-black text-brand-burgundy';
        }

        if (finalizacaoTitle) {
            finalizacaoTitle.textContent = finished
                ? 'Mobilizacao finalizada e veiculo apto.'
                : 'Mobilizacao ainda nao finalizada.';
        }

        if (finalizacaoDescription) {
            finalizacaoDescription.textContent = finished
                ? 'Todas as etapas possuem os dados minimos para encerramento do processo.'
                : 'O processo so deve ser encerrado quando houver evidencia de solicitacao, TAG, subcontratacao, SVG, aprovacao Vale e vistoria.';
        }

        if (finalizacaoProgress) {
            finalizacaoProgress.textContent = `${completed} de ${total} criterios concluidos automaticamente.`;
        }

        if (finalizacaoSubmit) {
            finalizacaoSubmit.disabled = !finished;
        }

        updateStepperProgress();
    };

    document.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', updateAutoChecks);
        field.addEventListener('change', updateAutoChecks);
        field.addEventListener('input', updateFinalizacao);
        field.addEventListener('change', updateFinalizacao);
    });

    updateAutoChecks();
    updateFinalizacao();

    const closeModal = (modal) => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.querySelector(button.getAttribute('data-modal-open'));

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-modal]');

            if (modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('[data-modal-backdrop]').forEach((backdrop) => {
        backdrop.addEventListener('click', () => {
            const modal = backdrop.closest('[data-modal]');

            if (modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-modal]:not(.hidden)').forEach(closeModal);

        if (sidebar && !isDesktopNav() && sidebar.classList.contains('translate-x-0')) {
            setMobileNavOpen(false);
        }
    });
});

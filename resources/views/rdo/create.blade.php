@extends('layouts.app')

@section('title', 'Novo RDO - Omega286')
@section('eyebrow', 'Operação')
@section('page-title', 'Novo RDO')

@section('actions')
    <a href="{{ route('rdo.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 py-2 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Voltar
    </a>
@endsection

@section('content')
    @php
        $input = 'h-12 w-full rounded-xl border border-zinc-200 bg-white px-3 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:h-11 sm:rounded-lg sm:text-sm';
        $label = 'text-xs font-bold uppercase tracking-wide text-brand-gray';
        $card = 'rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-5';
    @endphp

    <section class="mb-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:mb-5 sm:rounded-xl sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Diário de bordo</p>
                <h2 class="mt-1 text-2xl font-black leading-tight text-brand-black sm:text-xl sm:font-bold">Registro diário da equipe</h2>
                <p class="mt-1 text-sm text-brand-gray">Preencha em campo. Se estiver sem internet, salve offline e transmita depois.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm font-semibold">
                <span id="rdo-online-state" class="rounded-full border border-zinc-200 bg-brand-gray-soft px-3 py-1 text-brand-gray">Verificando conexão...</span>
                <span id="rdo-pending-count" class="rounded-full border border-brand-burgundy/20 bg-brand-burgundy-soft px-3 py-1 text-brand-burgundy">0 pendentes</span>
            </div>
        </div>
    </section>

    <nav class="sticky top-20 z-[9] -mx-4 mb-4 overflow-x-auto border-y border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur sm:hidden">
        <div class="flex min-w-max gap-2">
            <a href="#rdo-dados" class="rounded-full bg-brand-burgundy px-4 py-2 text-xs font-bold text-white">Dados</a>
            <a href="#rdo-atividades" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-bold text-brand-black">Atividades</a>
            <a href="#rdo-equipe" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-bold text-brand-black">Equipe</a>
            <a href="#rdo-fechamento" class="rounded-full border border-zinc-200 bg-white px-4 py-2 text-xs font-bold text-brand-black">Fechamento</a>
        </div>
    </nav>

    <form id="rdo-form" method="POST" action="{{ route('rdo.store') }}" enctype="multipart/form-data" class="space-y-4 pb-28 sm:space-y-5 sm:pb-0">
        @csrf
        <input type="hidden" name="offline_uuid" id="offline_uuid">

        <section id="rdo-dados" class="{{ $card }} scroll-mt-36">
            <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Cabeçalho</p>
            <h2 class="mt-1 text-lg font-bold text-brand-black">Dados principais</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <label class="space-y-2">
                    <span class="{{ $label }}">Data</span>
                    <input type="date" name="data" value="{{ old('data', optional($rdo->data)->format('Y-m-d')) }}" required class="{{ $input }}">
                </label>
                <label class="space-y-2 md:col-span-2">
                    <span class="{{ $label }}">Título</span>
                    <input name="titulo" value="{{ old('titulo', 'Relatório de obra') }}" class="{{ $input }}" placeholder="Relatório de obra">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Contrato</span>
                    <input name="contrato" class="{{ $input }}" placeholder="Ex.: 286">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Frente</span>
                    <input name="frente" class="{{ $input }}" placeholder="Ex.: Turma Mecânica">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Área</span>
                    <input name="area" class="{{ $input }}" placeholder="Ex.: Salobo N1">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Disciplina</span>
                    <input name="disciplina" class="{{ $input }}" placeholder="Mecânica, caldeiraria...">
                </label>
                <label class="space-y-2">
                    <span class="{{ $label }}">Condição climática</span>
                    <input name="condicao_climatica" class="{{ $input }}" placeholder="Normal, chuva, interdição...">
                </label>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            <div class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Responsáveis</p>
                <h2 class="mt-1 text-lg font-bold text-brand-black">Supervisão e encarregado</h2>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2">
                        <span class="{{ $label }}">Supervisor</span>
                        <input name="supervisor_nome" class="{{ $input }}">
                    </label>
                    <label class="space-y-2">
                        <span class="{{ $label }}">Matrícula</span>
                        <input name="supervisor_matricula" class="{{ $input }}">
                    </label>
                    <label class="space-y-2">
                        <span class="{{ $label }}">Encarregado</span>
                        <input name="encarregado_nome" class="{{ $input }}">
                    </label>
                    <label class="space-y-2">
                        <span class="{{ $label }}">Matrícula</span>
                        <input name="encarregado_matricula" class="{{ $input }}">
                    </label>
                </div>
            </div>

            <div class="{{ $card }}">
                <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Evidência</p>
                <h2 class="mt-1 text-lg font-bold text-brand-black">Foto ou documento</h2>
                <p class="mt-1 text-sm text-brand-gray">Use a câmera do celular em campo ou anexe um arquivo já salvo.</p>

                <div class="mt-5 grid gap-3 sm:hidden">
                    <button type="button" data-rdo-camera-open class="flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-burgundy px-4 text-sm font-black text-white shadow-sm shadow-brand-burgundy/20">
                        <i data-lucide="camera" class="h-4 w-4"></i>
                        Tirar foto
                    </button>
                    <label class="flex h-12 cursor-pointer items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-4 text-sm font-black text-brand-black shadow-sm">
                        <i data-lucide="paperclip" class="h-4 w-4"></i>
                        Anexar arquivo
                        <input type="file" name="evidencia" accept="image/*,.pdf" class="sr-only" data-rdo-evidencia-input>
                    </label>
                    <p data-rdo-evidencia-name class="rounded-xl bg-brand-gray-soft px-3 py-2 text-sm font-semibold text-brand-gray">Nenhuma evidência selecionada.</p>
                </div>

                <input type="file" name="evidencia" accept="image/*,.pdf" class="mt-5 hidden h-12 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-brand-gray file:mr-3 file:rounded-lg file:border-0 file:bg-brand-burgundy file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white sm:block sm:h-11 sm:rounded-lg sm:file:rounded-md sm:file:py-1.5" data-rdo-evidencia-input>
                <input type="hidden" name="evidencia_base64" data-rdo-camera-data>
            </div>
        </section>

        <section id="rdo-atividades" class="{{ $card }} scroll-mt-36">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Linha do tempo</p>
                    <h2 class="mt-1 text-lg font-bold text-brand-black">Atividades do dia</h2>
                </div>
                <button type="button" data-add-atividade class="inline-flex h-10 items-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 text-xs font-semibold text-brand-black shadow-sm hover:border-brand-burgundy hover:text-brand-burgundy sm:h-9 sm:rounded-lg">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Atividade
                </button>
            </div>
            <div data-atividades class="mt-5 space-y-3">
                @foreach ([0, 1, 2] as $index)
                    <div class="grid gap-3 rounded-2xl border border-zinc-200 bg-brand-gray-soft/40 p-3 sm:rounded-xl sm:p-4 md:grid-cols-[120px_120px_1fr]">
                        <input name="atividades[{{ $index }}][inicio]" placeholder="Início" class="{{ $input }}">
                        <input name="atividades[{{ $index }}][fim]" placeholder="Fim" class="{{ $input }}">
                        <input name="atividades[{{ $index }}][descricao]" placeholder="Descreva o que foi feito..." class="{{ $input }}">
                    </div>
                @endforeach
            </div>
        </section>

        <section id="rdo-equipe" class="{{ $card }} scroll-mt-36">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-burgundy">Equipe</p>
                    <h2 class="mt-1 text-lg font-bold text-brand-black">Efetivo envolvido</h2>
                    <p class="mt-1 text-sm text-brand-gray">Selecione os colaboradores que participaram das atividades do dia.</p>
                </div>
            </div>

            <div class="mt-5 hidden overflow-hidden rounded-xl border border-zinc-200 sm:block">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead class="bg-brand-gray-soft text-xs font-bold uppercase tracking-wide text-brand-gray">
                            <tr>
                                <th class="w-16 px-4 py-3">Check</th>
                                <th class="w-36 px-4 py-3">Matrícula</th>
                                <th class="px-4 py-3">Nome</th>
                                <th class="w-72 px-4 py-3">Função</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @forelse ($colaboradores as $index => $colaborador)
                                <tr data-equipe-item class="transition hover:bg-brand-gray-soft/60">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" data-rdo-equipe-check class="h-5 w-5 rounded border-zinc-300 accent-[#6f1731]">
                                        <input type="hidden" name="equipe[{{ $index }}][funcao]" value="{{ $colaborador->cargo }}" disabled>
                                        <input type="hidden" name="equipe[{{ $index }}][nome]" value="{{ $colaborador->nome }}" disabled>
                                        <input type="hidden" name="equipe[{{ $index }}][matricula]" value="{{ $colaborador->matricula }}" disabled>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-brand-black">{{ $colaborador->matricula ?: '-' }}</td>
                                    <td class="px-4 py-3 font-semibold text-brand-black">{{ $colaborador->nome }}</td>
                                    <td class="px-4 py-3 text-brand-gray">{{ $colaborador->cargo ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-sm text-brand-gray">Nenhum colaborador ativo cadastrado no Efetivo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-5 space-y-3 sm:hidden">
                @forelse ($colaboradores as $index => $colaborador)
                    <label data-equipe-item class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition active:scale-[0.99]">
                        <input type="checkbox" data-rdo-equipe-check class="h-6 w-6 rounded border-zinc-300 accent-[#6f1731]">
                        <input type="hidden" name="equipe[{{ $index }}][funcao]" value="{{ $colaborador->cargo }}" disabled>
                        <input type="hidden" name="equipe[{{ $index }}][nome]" value="{{ $colaborador->nome }}" disabled>
                        <input type="hidden" name="equipe[{{ $index }}][matricula]" value="{{ $colaborador->matricula }}" disabled>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-base font-black text-brand-black">{{ $colaborador->nome }}</span>
                            <span class="mt-1 block text-sm font-semibold text-brand-gray">{{ $colaborador->matricula ?: 'Sem matrícula' }} · {{ $colaborador->cargo ?: 'Função não informada' }}</span>
                        </span>
                    </label>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-6 text-center text-sm text-brand-gray">
                        Nenhum colaborador ativo cadastrado no Efetivo.
                    </div>
                @endforelse
            </div>
        </section>

        <section id="rdo-fechamento" class="grid scroll-mt-36 gap-4 lg:grid-cols-2">
            <label class="space-y-2 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-5">
                <span class="{{ $label }}">Observações</span>
                <textarea name="observacoes" rows="5" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-3 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:rounded-lg sm:text-sm" placeholder="Informações gerais do dia..."></textarea>
            </label>
            <label class="space-y-2 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:rounded-xl sm:p-5">
                <span class="{{ $label }}">Ocorrências</span>
                <textarea name="ocorrencias" rows="5" class="w-full rounded-xl border border-zinc-200 bg-white px-3 py-3 text-base outline-none transition focus:border-brand-burgundy focus:ring-2 focus:ring-brand-burgundy/10 sm:rounded-lg sm:text-sm" placeholder="Interferências, atrasos, bloqueios, segurança..."></textarea>
            </label>
        </section>

        <div class="hidden flex-col-reverse gap-3 sm:flex sm:flex-row sm:justify-end">
            <button type="button" data-save-offline class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-4 text-sm font-semibold text-brand-black shadow-sm transition hover:border-brand-burgundy hover:text-brand-burgundy">
                <i data-lucide="wifi-off" class="h-4 w-4"></i>
                Salvar offline
            </button>
            <button class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-brand-burgundy px-5 text-sm font-semibold text-white shadow-sm shadow-brand-burgundy/20 transition hover:bg-brand-burgundy-dark">
                <i data-lucide="send" class="h-4 w-4"></i>
                Transmitir RDO
            </button>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white/95 p-3 shadow-[0_-8px_30px_rgba(0,0,0,0.08)] backdrop-blur sm:hidden">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-save-offline class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-zinc-200 bg-white px-3 text-sm font-black text-brand-black shadow-sm">
                    <i data-lucide="wifi-off" class="h-4 w-4"></i>
                    Offline
                </button>
                <button class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-brand-burgundy px-3 text-sm font-black text-white shadow-sm shadow-brand-burgundy/20">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    Transmitir
                </button>
            </div>
        </div>

        <div data-rdo-camera-modal class="fixed inset-0 z-40 hidden bg-black/80 p-4 sm:hidden">
            <div class="flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-zinc-200 p-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-brand-burgundy">Câmera</p>
                        <h3 class="text-lg font-black text-brand-black">Registrar evidência</h3>
                    </div>
                    <button type="button" data-rdo-camera-close class="flex h-10 w-10 items-center justify-center rounded-xl border border-zinc-200 bg-white text-brand-black">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="min-h-0 flex-1 bg-black">
                    <video data-rdo-camera-video class="h-full w-full object-cover" playsinline autoplay muted></video>
                    <canvas data-rdo-camera-canvas class="hidden"></canvas>
                </div>
                <div class="grid grid-cols-2 gap-2 border-t border-zinc-200 p-3">
                    <button type="button" data-rdo-camera-close class="h-12 rounded-xl border border-zinc-200 bg-white text-sm font-black text-brand-black">Cancelar</button>
                    <button type="button" data-rdo-camera-capture class="h-12 rounded-xl bg-brand-burgundy text-sm font-black text-white">Usar foto</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    @include('rdo.partials.offline-sync-script')
    <script>
        (() => {
            const form = document.getElementById('rdo-form');
            if (!form) return;

            let atividadeIndex = form.querySelectorAll('[data-atividades] > div').length;
            const inputClass = @json($input);

            const row = (index) => `<div class="grid gap-3 rounded-2xl border border-zinc-200 bg-brand-gray-soft/40 p-3 sm:rounded-xl sm:p-4 md:grid-cols-[120px_120px_1fr]">
                <input name="atividades[${index}][inicio]" placeholder="Início" class="${inputClass}">
                <input name="atividades[${index}][fim]" placeholder="Fim" class="${inputClass}">
                <input name="atividades[${index}][descricao]" placeholder="Descreva o que foi feito..." class="${inputClass}">
            </div>`;

            form.querySelector('[data-add-atividade]')?.addEventListener('click', () => {
                form.querySelector('[data-atividades]').insertAdjacentHTML('beforeend', row(atividadeIndex++));
            });

            form.querySelectorAll('[data-rdo-equipe-check]').forEach((checkbox) => {
                const item = checkbox.closest('[data-equipe-item]');
                const hiddenFields = Array.from(item.querySelectorAll('input[type="hidden"]'));

                checkbox.addEventListener('change', () => {
                    hiddenFields.forEach((field) => field.disabled = !checkbox.checked);
                    item.classList.toggle('border-brand-burgundy', checkbox.checked);
                    item.classList.toggle('bg-brand-burgundy-soft/40', checkbox.checked);
                });
            });

            form.querySelectorAll('[data-rdo-evidencia-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    const label = form.querySelector('[data-rdo-evidencia-name]');
                    const cameraData = form.querySelector('[data-rdo-camera-data]');

                    form.querySelectorAll('[data-rdo-evidencia-input]').forEach((otherInput) => {
                        if (otherInput !== input) {
                            otherInput.value = '';
                        }
                    });

                    if (cameraData) {
                        cameraData.value = '';
                    }

                    if (label) {
                        label.textContent = file ? file.name : 'Nenhuma evidência selecionada.';
                        label.classList.toggle('text-brand-burgundy', Boolean(file));
                    }
                });
            });

            const cameraModal = form.querySelector('[data-rdo-camera-modal]');
            const cameraVideo = form.querySelector('[data-rdo-camera-video]');
            const cameraCanvas = form.querySelector('[data-rdo-camera-canvas]');
            const cameraData = form.querySelector('[data-rdo-camera-data]');
            let cameraStream = null;

            const stopCamera = () => {
                cameraStream?.getTracks().forEach((track) => track.stop());
                cameraStream = null;
                cameraModal?.classList.add('hidden');
            };

            form.querySelector('[data-rdo-camera-open]')?.addEventListener('click', async () => {
                if (!navigator.mediaDevices?.getUserMedia) {
                    alert('Câmera indisponível neste navegador. Use a opção Anexar arquivo.');
                    return;
                }

                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: { ideal: 'environment' } },
                        audio: false,
                    });

                    cameraVideo.srcObject = cameraStream;
                    cameraModal?.classList.remove('hidden');
                } catch (error) {
                    alert('Não foi possível acessar a câmera. Verifique a permissão do navegador ou use Anexar arquivo.');
                }
            });

            form.querySelectorAll('[data-rdo-camera-close]').forEach((button) => {
                button.addEventListener('click', stopCamera);
            });

            form.querySelector('[data-rdo-camera-capture]')?.addEventListener('click', () => {
                if (!cameraVideo?.videoWidth || !cameraCanvas || !cameraData) return;

                cameraCanvas.width = cameraVideo.videoWidth;
                cameraCanvas.height = cameraVideo.videoHeight;
                cameraCanvas.getContext('2d').drawImage(cameraVideo, 0, 0);
                cameraData.value = cameraCanvas.toDataURL('image/jpeg', 0.86);

                form.querySelectorAll('[data-rdo-evidencia-input]').forEach((input) => {
                    input.value = '';
                });

                const label = form.querySelector('[data-rdo-evidencia-name]');
                if (label) {
                    label.textContent = 'Foto capturada pela câmera.';
                    label.classList.add('text-brand-burgundy');
                }

                stopCamera();
            });

            form.querySelectorAll('[data-save-offline]').forEach((button) => {
                button.addEventListener('click', async () => {
                    await window.RdoOfflineQueue?.saveForm(form);
                    alert('RDO salvo offline. Ele será transmitido quando houver internet.');
                });
            });

            form.addEventListener('submit', async (event) => {
                if (navigator.onLine) return;

                event.preventDefault();
                await window.RdoOfflineQueue?.saveForm(form);
                alert('Sem internet. RDO salvo offline para transmissão automática.');
            });
        })();
    </script>
@endpush

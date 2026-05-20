# Auditoria — Apuração do Ponto (matriz de cálculo)

Documento de referência após revisão RH/jurídica em 2026-05. Fonte de verdade: `FrequenciaCalculo`, `ApuracaoPontoMetricas` e `CartaoPontoService`.

## Fluxo de montagem de cada dia

1. `ColaboradorVinculoPonto::contaPontoNaData` — ignora antes da admissão / após demissão.
2. Se há batidas → `linhaComBatidas` (métricas via `ApuracaoPontoMetricas::calcular`).
3. Senão: feriado, justificado, folga, escala sem jornada, ou `linhaVazia` (falta integral).
4. Cálculo numérico via `FrequenciaCalculo::resumoParaApuracao()`.

## Campo a campo (linha com batidas)

| Coluna na tela | Origem | Fórmula / regra |
|----------------|--------|------------------|
| **Ent. 1–Sai. 2** | Banco `frequencia_registros` | Exibição `HH:MM` + sufixo origem (C/I/P/M). |
| **Total trab.** | `minutosTrabalhados` | Soma dos pares entrada/saída válidos. Fallback da escala **somente** para `saida_1`/`entrada_2` vazios quando existem `entrada_1` e `saida_2` (não fabrica jornada inteira). |
| **H. previstas** | `jornadaMinutosParaRegistro` | Segmentos da escala do dia (ou 480 min padrão). Folga/feriado = 0. |
| **Dia falta (integral)** | `dia_falta_integral` | `1` somente se falta do dia inteiro (sem batidas com jornada prevista, ou déficit ≥ jornada − tolerância). Falta parcial **não** vira 1 dia. |
| **Horas falta** | `minutos_falta` | `max(0, previstas − trabalhadas)` após tolerância diária (padrão **10 min**, CLT/Súmula 366). |
| **H. atraso** | `atraso_bruto` | `primeira_entrada` real − `entrada_1` da escala (informativo; **não** somado ao desconto). |
| **Atraso descont.** | `atraso_descontavel` | Parte do atraso que entrou no déficit (`min(atraso_bruto, minutos_falta)`). |
| **Horas extras** | `minutos_extras` | `max(0, trabalhadas − previstas)` após tolerância. Entrada antecipada **não** vira extra automaticamente. |
| **Entr. antec. / Sai. post.** | Campos separados | Comparando primeira entrada / última saída com escala (informativo). |
| **Falta+atraso** | `total_desconto` | Igual a **horas falta** (não soma atraso bruto de novo). |
| **Ok (✓/✗)** | `apurado` | ✓ se não há falta descontável nem inconsistência crítica. |

## Absenteísmo gerencial

| Conceito | Regra |
|----------|--------|
| **Horas injustificadas** | `minutos_falta` do dia (déficit real, não “atraso + falta” duplicado). |
| **Taxa** | `horas_ausencia / horas_previstas` (ex.: 20 min em 8 h ≈ 0,04 dia, não 1 dia). |
| **Contagem de dias (`ausencias`)** | Apenas `dia_falta_integral` e faltas/justificados de dia inteiro. |

## Tolerância

- Padrão: `RH_FREQUENCIA_TOLERANCIA_FALTA_MINUTOS` = **10** (não usar 5% da jornada como padrão).
- Abaixo do limite: sem falta descontável; absenteísmo injustificado = 0 para aquele dia.

## Dias especiais (sem cálculo de batidas)

| Situação | Comportamento |
|----------|----------------|
| Feriado / justificado / folga | Linha rótulo; totais zerados; `apurado = true`. |
| Fora do vínculo | Rótulo “Antes da admissão” / “Após demissão”; não conta falta. |
| Dia de jornada sem batidas | `horas_falta` = jornada prevista; `dia_falta_integral = 1`. |

## Escala rotativa (motoristas)

`HorarioEscalaSemanalAlternada`: template `dia_semana = 1` para seg/qua/sex ou ter/qui conforme semana ímpar/par.

## Variáveis de ambiente

- `RH_FREQUENCIA_JORNADA_MINUTOS` (padrão 480) — jornada quando sem escala no dia.
- `RH_FREQUENCIA_TOLERANCIA_FALTA_MINUTOS` (padrão 10) — limite diário de variação sem desconto.

## Pendências conhecidas

- **Adicional noturno**: não implementado (`00:00`).
- **Total normais**: calculado; exibição web limitada (PDF legado).

## Testes automatizados

- `tests/Feature/Rh/CartaoPontoApuracaoTest.php` — desconto não duplicado, tolerância, admissão, motorista.
- `tests/Feature/Rh/AbsenteismoHorasTest.php` — horas por déficit real, dias integrais separados.

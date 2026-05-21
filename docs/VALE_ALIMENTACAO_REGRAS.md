# Vale alimentação — regras e onde está no sistema

## Onde ver e usar

| Local | Função |
|-------|--------|
| **RH → Benefícios → Editar** | Valor mensal base (ex.: R$ 750,00) |
| **RH → Benefícios → Extrato de valores** (passo 1) | Selecionar benefícios do extrato |
| **RH → Benefícios → Extrato → Regras** (passo 2) | Modal de configuração por benefício (vigência anual, faixas, Natal, acidente) |
| **RH → Benefícios → Extrato → Gerar** (passo 3) | Colaborador + período → extrato consolidado |
| **RH → Frequência / Apuração de ponto** | Fonte das faltas (justificada x injustificada) |

Código: `App\Services\Rh\ValeAlimentacaoCalculoService`, `App\Services\Rh\BeneficioExtratoCalculoService`  
Testes: `tests/Feature/Rh/ValeAlimentacaoCalculoTest.php`, `tests/Feature/Rh/BeneficioExtratoTest.php`

## Regras implementadas (MVP)

### 1. Valor base

Valor do campo **Valor** no cadastro do benefício (ex. VALE ALIMENTAÇÃO / ALELO001).

### 2. Desconto por assiduidade (período)

Configurável no modal **Vale / Auxílio Alimentação** (faixas de faltas × %). Padrão ACT:

| Faltas injustificadas | Desconto |
|----------------------|----------|
| 0 | 0% (valor integral) |
| 1 | 20% |
| 2 | 50% |
| 3+ | 100% |

Faltas **injustificadas** somadas na **apuração de ponto** entre **período inicial** e **período final** do extrato.

- Conta apenas **dia de falta integral** (`minutos_dia_falta` na apuração).
- **Não** conta: `status = justificado`, folga, feriado.
- Alinhado a `CartaoPontoService` / `ApuracaoPontoMetricas`.

### 3. Admissão / demissão no mês

Proporcional: `(dias úteis com vínculo no mês) / (dias úteis do mês na escala)`.

### 4. Afastamento por acidente de trabalho

1. Registrar em **Efetivo → ficha do colaborador → Movimentação → Afastamento INSS / benefício**.
2. Espécie: **Acidente de trabalho (CAT / afastamento)** (`especie_beneficio_inss = acidente_trabalho`).
3. Informar **início do afastamento**; **data fim** opcional (em aberto se ainda afastado).
4. No extrato (modal Vale), ativar **Afastamento por acidente de trabalho** e definir **meses com vale integral** (padrão: 3).

**Cálculo:** no mês de referência do extrato (fim do período de apuração), conta-se o mês de calendário desde o início do afastamento (1º, 2º, 3º…). Enquanto ≤ limite configurado e o afastamento cobre o mês, **não há desconto por falta** no vale. Após o limite, voltam as faixas de assiduidade.

Código: `App\Support\Rh\AfastamentoAcidenteTrabalho`, testes `AfastamentoAcidenteTrabalhoTest`, `ValeAlimentacaoCalculoTest` (cenários de acidente).

### 5. Recarga extra de Natal

Configurável no modal do extrato (vigência, atestados, cargos excluídos). Ver parâmetros em `ValeAlimentacaoRegraConfig`.

## Comando de diagnóstico

```bash
php artisan beneficios:diagnostico 1
```

## Deploy

```bash
git pull origin main
php artisan optimize:clear
php artisan view:clear
```

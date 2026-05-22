# Afastamento INSS — Chamados de Movimentação RH

## Tipo de chamado

- **Código:** `afastamento_inss`
- **Label:** Afastamento INSS / Previdenciário
- **URL:** `/rh/chamados-movimentacao/criar?tipo=afastamento_inss`

## Status específicos (chamado)

Além dos status gerais do módulo, foram adicionados:

`atestado_recebido`, `em_analise_rh`, `aguardando_envio_esocial`, `aguardando_requerimento_inss`, `aguardando_pericia_inss`, `aguardando_resultado_inss`, `beneficio_deferido`, `beneficio_indeferido`, `beneficio_prorrogado`, `aguardando_retorno_trabalho`, `aguardando_aso_retorno`, `retorno_liberado`, `retorno_bloqueado`, `afastamento_encerrado`, `aguardando_finalizacao`, `concluido`.

## Fluxo automático (11 etapas)

1. Registro inicial do atestado  
2. Triagem do RH (12 itens de checklist)  
3. Classificação do afastamento  
4. Validação TST/SSMA — **condicional** (acidente/trajeto/ocupacional)  
5. DP/Folha/eSocial  
6. Encaminhamento ao INSS — **condicional** (>15 dias ou classificação previdenciária)  
7. Benefícios  
8. Acompanhamento do afastamento  
9. Retorno de afastamento — **condicional** (retorno/alta)  
10. ASO de retorno — **condicional** (≥30 dias ou retorno ao trabalho)  
11. Finalização do afastamento  

## Abertura do chamado

Campos na tela de criação + anexo obrigatório de atestado. Dados sensíveis em `dados_depois_json`. CID opcional (não obrigatório).

Classificação sugerida automaticamente se >15 dias ou tipo acidente.

## Finalização

- Cadastro do colaborador **só altera** ao clicar **Finalizar processo**.
- Exige: etapas obrigatórias concluídas, classificação, resultado final, atestado anexado, ASO quando aplicável.
- Gera registro em `colaborador_movimentacoes` (tipo `afastamento_inss`).

## Ficha do colaborador

Aba **Histórico de afastamentos** (`#afastamentos`) com chamados INSS e link para abrir o processo.

## Prorrogação

Campo `chamado_origem_id` — abrir novo chamado com `?chamado_origem={id}`.

## Fase 2 (pendente)

- UI item a item dos checklists (OK/pendente/NA/corrigido/devolvido)
- Formulários por etapa (INSS, eSocial, acompanhamento periódico)
- Perfis gestor vs RH/DP/TST (máscara CID/laudos)
- PDF do processo
- Auto-suspensão de benefícios (somente após etapa validada)

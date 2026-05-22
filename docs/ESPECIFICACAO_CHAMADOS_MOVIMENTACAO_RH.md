# Chamados de Movimentação de RH — Especificação

> Documento oficial do produto. O fluxo anterior (registro direto + `situacao` pendente/finalizada em `colaborador_movimentacoes`) está **substituído** por este módulo.

## Princípio central

**Nenhuma movimentação altera o cadastro do colaborador na abertura.**  
Abre-se um **chamado** com etapas, responsáveis, prazos, evidências e aprovações. Só na **finalização** o sistema aplica a alteração no cadastro (via `MovimentacaoFinalizacaoService`).

## Tipos iniciais

1. Desligamento  
2. Transferência de contrato  
3. Promoção  
4. Mudança de função  

*(Futuro: afastamento, retorno, alteração salarial, S-2205/2206, etc.)*

## Status do chamado

`aberto` · `em_triagem` · `aguardando_aprovacao` · `aguardando_documentos` · `aguardando_exame_aso` · `aguardando_dp_folha` · `aguardando_mobilizacao` · `em_execucao` · `concluido` · `cancelado` · `reprovado`

Status da etapa: `pendente` · `em_andamento` · `concluida` · `reprovada` · `dispensada` · `bloqueada`

## Protocolo

Formato: `MOV-RH-{ANO}-{SEQUENCIA}` (ex.: `MOV-RH-2026-0001`)

## Tabelas

- `rh_movimentacao_chamados`
- `rh_movimentacao_etapas`
- `rh_movimentacao_checklist_itens`
- `rh_movimentacao_anexos`
- `rh_movimentacao_aprovacoes`
- `rh_movimentacao_comentarios`
- `rh_movimentacao_logs`

## Perfis (permissões — evolução)

Administrador RH · RH Operacional · DP/Folha · TST/SSMA · Gestor · Mobilização · Benefícios · Consulta

## Fluxos detalhados

Ver prompt completo na conversa de produto (seções 8–11: Desligamento 9 etapas, Transferência 8, Promoção 7, Mudança de função 7).

### Desligamento — etapas

1. Solicitação de desligamento  
2. Triagem do RH  
3. Aprovação do desligamento  
4. Comunicação ao colaborador  
5. Exame demissional / ASO  
6. DP/Folha/eSocial  
7. Benefícios e acessos  
8. Mobilização / Cliente / Operação  
9. Finalização do desligamento  

## Services

- `MovimentacaoChamadoService`
- `MovimentacaoWorkflowService`
- `MovimentacaoFinalizacaoService`
- `MovimentacaoChecklistService`
- `MovimentacaoAnexoService`
- `MovimentacaoLogService`

## Critérios de aceite (resumo)

1. Abrir chamado **sem** alterar cadastro  
2. Etapas automáticas por tipo  
3. Etapa com responsável, checklist, anexo, observação  
4. Bloqueio de finalização se faltar etapa obrigatória  
5. Histórico completo  
6. Cadastro atualizado **somente** na finalização  
7. Painel gerencial  
8. PDF do processo (fase 2)  
9. Configuração de fluxos sem código pesado (fase 2 — tela admin; fase 1 — `config/movimentacao_workflows.php`)

## Referências trabalhistas

eSocial S-2299 (desligamento), S-2206 (alteração contratual), S-2205 (cadastral); NR-7 exames demissional e mudança de risco.

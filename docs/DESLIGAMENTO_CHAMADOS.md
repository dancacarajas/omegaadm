# Chamados de Desligamento — RH

Módulo: `/rh/chamados-movimentacao` (tipo **Desligamento**).

## Fluxo (11 etapas)

1. Solicitação de desligamento  
2. Cadastro do desligamento no SIGO *(obrigatório)*  
3. Nada Consta Demissional *(obrigatório)*  
4. Triagem do RH  
5. Aprovação do desligamento  
6. Comunicação ao colaborador  
7. Exame demissional / ASO  
8. DP/Folha/eSocial  
9. Benefícios e acessos  
10. Mobilização / Cliente / Operação  
11. Finalização do desligamento  

## Regras de bloqueio

- **SIGO:** cadastro confirmado + data + responsável + anexos base (folha de ponto, Nada Consta assinado, documento do desligamento; carta de demissão se `pedido_demissao`).
- **Nada Consta:** todos os itens conferidos; débitos com tratativa e anexos (evidência, termo de baixa ou autorização de desconto); validação RH.
- **DP/Folha:** checklist da etapa marcado + mesmas validações de SIGO/Nada Consta.
- **Finalizar:** todas as etapas obrigatórias concluídas + regras acima.

## Permissões (perfil `rh`)

| Chave | Uso |
|-------|-----|
| `chamados_movimentacao_editar` | Editar chamado, SIGO, anexos |
| `chamados_movimentacao_validar_rh` | Validar Nada Consta (RH) |
| `chamados_movimentacao_area_{area}` | Editar itens da área no Nada Consta |

Áreas: `almoxarifado_obra`, `almoxarifado_central`, `patrimonio`, `transportes`.

Usuário **sem perfil** mantém acesso total (compatibilidade).

## PDF

- Visualizar: `GET /rh/chamados-movimentacao/{id}/pdf`
- Ao **finalizar**, o sistema gera PDF completo e grava como anexo `chamado_resumo_pdf`.

## Tabelas

- `rh_movimentacao_nada_consta`
- `rh_movimentacao_nada_consta_itens` (anexos por item: evidência, termo de baixa, autorização de desconto)

## Checklist automático

Itens de checklist são marcados como **OK** automaticamente quando o chamado já contém os dados (ex.: datas na abertura, SIGO salvo, pacote de documentos enviado, Nada Consta validado). A sincronização roda ao abrir o chamado, salvar SIGO/anexos/Nada Consta e na progressão automática de etapas.

## Testes

```bash
php artisan test --filter=MovimentacaoChamado
```

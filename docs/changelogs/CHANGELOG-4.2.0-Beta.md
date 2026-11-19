### Lista de melhorias e correções de problemas

#### Recusa "não possui andamento aberto na unidade" na sincronização de metadados (#1278)

Na devolução ou sincronização de um processo compartilhado entre órgãos, quando o órgão de origem havia cancelado um documento, o recebimento podia ser recusado com "Processo ... não possui andamento aberto na unidade ... Tarefa: 126". O cancelamento conclui o processo na unidade receptora, e a troca de tipo de processo (tarefa 126) ou de prioridade (tarefa 137) feita pela sincronização exige processo aberto.

A sincronização de processo existente deixa de alterar o **tipo de processo**, a **prioridade** e os **assuntos**: cada órgão mantém os seus. Continuam sincronizados a especificação, os interessados, o nível de acesso e a hipótese legal. Detalhes em **USAGE.md**, seção "Metadados do Processo Sincronizados entre Órgãos".

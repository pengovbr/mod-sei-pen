# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.7)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Esta versão já é compatível com as seguintes versões do SEI:
-3.1.x até 4.0.3


Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #119 - Ícone tramitação em Lote

O ícone "Envio Externo de Processos em Lote" está aparecendo para todas as unidades do SEI (tela Controle de Processos), inclusive para aquelas que não estão mapeadas para utilização do barramento, o que tem gerado transtornos internos junto aos usuários do sistema. Solicito a gentileza de corrigir isso em nova versão do módulo, e deixá-lo disponível apenas para as unidades com permissão de uso do barramento.

#### Issue #124 - Recusa com justificativa com mais de 500 caracteres

O erro em questão está ocorrendo especificamente em trâmites recusados pela AGU, onde a justificava gerada possui cerca de 700 caracteres. O trâmite fica no status 8, no caso, aguardando ciência da recusa no remetente. O problema é que o remetente não consegue dar a ciência, pois na hora gera o seguinte erro:
"Valor possui tamanho superior a 500 caracteres"

#### Issue #125 - Tramitação em lote - desbloquear processo em caso de erro

Ao tentar tramitar o processo havendo erro na tramitação o sistema deverá desbloquear o processo.

#### Issue #127 - Documentos movidos sem anexo não tramita via barramento

Ao tentar tramitar um processo contendo um documento movido sem anexo o sistema apresenta erro informando que o componente digital não foi localizado. Porém o referido documento foi movido o que não justifica a permanência do bloqueio.





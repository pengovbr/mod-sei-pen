# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contem todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

O foco desta versão foi o ajuste nas pesquisas de unidades no Barramento para recuperar somente aquelas em que o Gestor de Protocolo permitiu o recebimento de processos e documento através de configuração adicional do Portal do Barramento de Serviços do PEN, nova possibilidade disponível na última versão do PEN.


#### [Issue #17] Recebimento de documentos anexados

Implementação de recebimento de processos contendo documentos anexados (documentos com referência a outro no mesmo processo). Apesar de não existir este conceito nos metadados do SEI, outros sistemas enviam documentos anexados a outro pelo Barramento, sendo necessário esta melhoria.


#### [Issue #22] Consulta rápida filtrar apenas unidades que podem receber trâmites externos

Ajuste no módulo para que a consulta de unidades retorne apenas aquelas unidades que foram configuradas no Portal do Barramento de Serviços para permitir o recebimento de processos e documentos.


#### [Issue #23] Correção de Erro Espécie do documento de ordem X não confere #23

Correção de inconsistência após atualização do mod-sei-pen, versão 2.0.0, com retransmissão de processos recebidos em versões antigas do módulo.


#### [Issue #16] Correção de Erro em classificação de tipo de conteúdo PDF como outros

Correção de falha na classificação correta do tipo de componente digital PDF que estava sendo classificado como tipo desconhecido OUTROS.


#### [Issue #11] Correção de Erro Método \[PenRelHipoteseLegalRecebidoRN.cadastrar\] existe como Conectado e Controlado

Corrigido falha ao cadastrar ou alterar um novo mapeamento de hipótese legal de para recebimento.


#### [Issue #9] Correção de Erro de Processos e Recibos não sendo recebidos na versão 2.0.0 (status 2 e 5)

Correção de problema no módulo (versão 2.0.0 e 2.0.1) em que os processos não estão sendo recebidos (parado em status 2) e nem os recibos de conclusão do trâmite (parado em status 5) devido ao uso de função do PHP desabilitada por padrão em algumas distribuições da linguagem.

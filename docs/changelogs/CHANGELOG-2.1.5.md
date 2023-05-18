# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.5)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #59 - Correção de erro ao receber documento cancelado com 0 bytes

Ajuste para apenas validar apenas os casos em que documento é não está cancelado. O módulo estava validando casos em que o anexo não existia, ocorrendo erro e recusa.


#### Issue #65 - Implementa lógica para manter hash ao modificar URLs dos órgãos

Criar lógica para evitar erro de inconsistência de hash dos documentos para os casos em que houve mudança nos endereços do SEI no órgão de origem (hostname).


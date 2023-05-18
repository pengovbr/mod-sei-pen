# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.6)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Esta versão já é compatível com as seguintes versões do SEI:
-3.1.x até 4.0.3


Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #123 - Erro ao tramitar campo observação de um assunto

Usuário relata que ocorre erro ao tramiter pelo barramento as informações adicionais de um processo. Isso está ocorrendo pois existem tags no campo observação que o SOAP considera como parte da mensagem.





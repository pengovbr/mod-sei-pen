# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contem todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #25 - Número de documentos do processo não confere com o registrado nos dados do processo bug

Correção de regra de pós-validação de integridade do processo para evitar falso-positivos em trâmites de processos anexados. A rotina estava considerando apenas os documentos do processo principal durante a validação da integridade.

#### Issue #29 - Validar estrutura de xml na verificação de disponibilidade do PEN

Modificado rotina de verificação da disponibilidade dos servisos do Barramento do PEN para analisar se o conteúdo retornado pela endereço do serviço trata-se de um arquivo xml válido.


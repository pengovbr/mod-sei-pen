# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contem todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #28 - Inicializar transação após download de componentes digitais do processo/documento

Otimizado o fluxo de recebimento de trâmites de processo para abrir conexões e transações com o banco de dados apenas após à finalização do download de todos os componentes digitais do processo ou documento avulso.


#### Issue #27 - Erro de tamanho do campo de complemento da identificação do documento bug

Corrigido a funcionalidade de envio de processos e documentos avulsos para limitar o campo de complemento da identificação do documento para impedir ultrapassar o tamanho máximo de 100 caracteres, limitação definida no modelo de dados do Barramento do PEN.


#### Issue #25 - Número de documentos do processo não confere com o registrado nos dados do processo bug

Correção de regra de pós-validação de integridade do processo para evitar falso-positivos em trâmites de processos anexados. A rotina estava considerando apenas os documentos do processo principal durante a validação da integridade.

#### Issue #29 - Validar estrutura de xml na verificação de disponibilidade do PEN

Modificado rotina de verificação da disponibilidade dos servisos do Barramento do PEN para analisar se o conteúdo retornado pela endereço do serviço trata-se de um arquivo xml válido.

#### Issue #30 - Ajustar ordenação de documentos apenas em processos anexados

Otimizado rotina de recebimento de processo para apenas aplicar o ajuste de ordem dos documentos recebidos pelo Barramento em processo que possuam outros processos internos anexados.


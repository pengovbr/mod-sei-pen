# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Esta versão já é compatível com as seguintes versões do SEI:
-3.1.x
-4.0.0

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #111 - Erro ao enviar processo contendo observação do Assunto ou Tempo de Guarda vazias

Caso um assunto da tabela de assuntos possui o campo observação ou tempo de guarda vazios, o trâmite não ocorre

#### Issue #110 - Processo restrito com documento sem hipótese legal 

Com o Parametro habilitar_hipotese_legal=1, criando um processo restrito, ao inserir um documento sem hipótese legal e tramitar o módulo não faz a validação das informações

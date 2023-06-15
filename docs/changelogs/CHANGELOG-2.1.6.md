# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.6)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

#### Issue #60 - Correção de erro ao substituir hierarquia de órgão sem pai (@UNIDADE_DESTINO_HIRARQUIA@)

O módulo estáva retornado no histórico '@UNIDADE_DESTINO_HIRARQUIA@' nos casos que não encontrava uma hierarquia superior. Agora retornará vazio.


#### Issue #63 - Ajuste em lógica para truncar textos longos

A lógica atual apresentava erros se a última palavra era menor que a reticência.


#### Issue #43 - Adicionado mensagens de ajuda na página de configuração do Barramento

Adicionado mensagens de orientação para o correto preenchimento por parte do usuário


#### Issue #44 - Obrigatoriedade na seleção de um Tipo de Processo publico e restrito para configuração do módulo

O sistema estava permitindo a configuração de processos sigilosos ou desativados, o que provocava erro posteriormente durante o recebimento


#### Issue #45 - Impedir a configuração de Tipos de Processos sem a devida indicação Classificação de Assunto

O sistema estava permitindo a configuração de processos sem configuração de assuntos, o que provocava erro posteriormente durante o recebimento

#### Issue #70 - Homologação do mod-sei-pen para funcionamento com SEI 3.1.7

Homologação e liberação do funcionamento do módulo para a versão 3.1.7 do SEI
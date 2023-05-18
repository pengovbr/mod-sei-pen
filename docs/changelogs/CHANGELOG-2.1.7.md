# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.7)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

#### Issue #64 - Correção de envio de processo contendo documento cancelado e sigiloso

Corrigido problema identificado pelo usuário em situação em que um documento sigiloso era cancelado no processo e posteriormente não conseguia mais ser trâmitado pelo Barramento de serviços. Mesmo este estando cancelado, o Barramento impedia o trâmite por haver documento sigiloso.


#### Issue #74 - Correção de envio de processo contendo documento cancelado contendo hipótese legal inativa

Corrigido problema identificado em situação em que um documento restrito com determinada hipótese legal inativa era cancelado no processo e posteriormente não conseguia mais ser trâmitado pelo Barramento de serviços. Mesmo este estando cancelado, o módulo impedia o trâmite devido a inexistência de uma hipótese válida.


#### Issue #76 - Correção de erro em cadastramento de mapeamento Hipótese Legal em duplicidade

Sistema estava permitindo o cadastramento de uma mapeamento de hipótese legal em duplicidade no sistema, o que provocava falha durante o envio de processos utilizando alguma destas hipóteses duplicadas.

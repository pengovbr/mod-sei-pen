# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.0.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contem todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

O foco desta versão foi a implementação de diversas simplificações nos procedimentos de instalação e configuração do módulo, deixando alguns passos opcionais e aplicando várias configurações de forma automática, possibilitando ao administrador modificar caso necessário.


#### [Issue #5] Correção de erro na edição dos mapeamentos de tipos de documentos para recebimento

Ao acessar a funcionalidade de alteração de mapeamento de tipos de documentos para recebimento, era apresentado erro "Método \[PenRelTipoDocMapRecebidoRN.consultar\]" não encontrado. 

#### [Issue #7] Correção de rejeição de processo por Método [ProcedimentoAndamentoRN.gravarLogDebug] não encontrado

Identificado erro durante o recebimento de processos em acertas circunstâncias em que é necessário realizar o particionamento de arquivos grandes. O problema ocorria no sistema destinatário do processo, o que provoca a sua rejeição para o sistema remetente.

#### [Issue #8] Não foi possível acessar localmente o webservice do mod-sei-pen em http://[endereco]/sei/modulos/pen/ws/ModPenWS.php

Erro ocorria sempre que o servidor Apache estava configurado com certificado digital HTTPS para criptografia da comunicação, fato comum em produção. Alterado implementação para executar toda a rotina de recebimento localmente, sem precisar de chamadas de webservice internas no mod-sei-pen.
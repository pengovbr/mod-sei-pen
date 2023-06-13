# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #71 - Enviar histórico do processo ao barramento

Enviar as informações do histórico de movimentação do processo ao PEN

#### Issue #72 - Enviar versão do módulo a cada trâmite

Enviar a versão como uma informação adicional ao PEN a cada trâmite do órgão.

#### Issue #73 - Enviar informações de classificação arquivistica ao PEN

Enviar a cada processo as informações de classificação do assunto, como período de guarda, etc.

#### Issue #75 - Tramitação em lote

Criação de nova funcionalidade que permita o envio em lote de processos para o barramento.

#### Issue #106 - Elaborar script de reboot automático da fila para quem usa apenas o agendamento do SEI 

Em alguns casos o processamento da fila de pendencias pode congelar. O processo fica no ar indefinidamente sem fazer nada. O agendamento do SEI acha que há coisa processada na fila e ignora a subida de novas threads de processamento.

#### Issue #109 - Utilizar a API v3 do Barramento

Consumir a nova versão da API do barramento










# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.2)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #31 - Correção de erro no trâmite de processos contendo documento movido

Correção de falha na rotina de envio de processos que informava ao Barramento de Serviços do PEN que um documento movido dentro do processo 
se tratava de um documento dentro de outro processo anexado. Esta falha fazia com que o documento movido fosse recebido pela instituição 
destinatária como um processo anexado. A correção aplicada faz com que os documentos nesta situação sejam assinalados como retirados do processo, 
sendo reconhecido pela instituição destinatária como cancelados.


#### Issue #34 - Correção de erro com a devolução de processos anexados para o sistema de origem

Correção de rotina de recebimento de processos anexados que foram devolvidos pela instituição destinatária do trâmite. 
Esta falha provocava uma tentativa de recadastramento dos documentos do processo anexado por não identificá-los como pré-existentes no processo. Neste 
caso, o recebimento do processo acabava sendo rejeitado pela rotina de validação final devido à inconsistência dos documentos, evitando 
problemas de falta de integridade, mas rejeitando recebimento do mesmo.


#### Issue #35 - Correção de erro no trâmite de processos contendos documentos cancelados

Correção de falha na rotina de validação de integridade do trâmite de processos, gerando um falso-positivo indicando que a quantidade de documentos 
do processo se encontrava inconsistênte durante o recebimento. Esta falha na validação ocorria em processos com documentos contendo documentos cancelados 
que não eram contabilizados nesta verificação.


#### Issue #36 - Correção de erro no download dos recibos de envio e conclusão do trâmite

Correção de falha que ocorrina na página de download dos recibos de envio e conclusão do trâmite do processo, gerando erro de falha na download dos dados do recibo devido a problema na abertura de conexão com o banco de dados.


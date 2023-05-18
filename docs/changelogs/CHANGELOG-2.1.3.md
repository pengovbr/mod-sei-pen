# NOTAS DE VERSÃO MOD-SEI-PEN (versão 2.1.3)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas


#### Issue #47 - Correção de erro ao tentar excluir mapeamento de tipos de documentos para envio bug

Correção de falha que ocorrina na página de download dos recibos de envio e conclusão do trâmite do processo, gerando erro de falha na download dos dados do recibo devido a problema na abertura de conexão com o banco de dados.


#### Issue #55 - Rejeição de processo por inconsistência em processos anexados

Correção de falha no recebimento de processos anexados em cenário em que o mesmo é tramitado mais de uma vez para a mesma instituição. Quando esta situação ocorria, documentos adicionados recentemente ao processo não eram identificados corretamente e o módulo tentava inseri-los novamente, o que gera inconsistência e rejeição do trâmite.


#### Issue #53 - Correção de erro em todo sistema por falta de configuração do módulo

Correção de falha provocada em diversos pontos do sistema por falta de determinadas configurações por parte do módulo. Modificado comportamento para validar apenas aqueles parâmetros básicos da instalação.


#### Issue #51 - Ajustes para considerar o php.ini correto na inicialização de processo de recebimento

Implementado ajuste na rotina de recebimento de pendências do Barramento de Serviços do PEN para iniciar o script paralelo de recebimento utilizando a configuração correta do php.ini definida no contexto da execução.

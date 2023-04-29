# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.3.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
    * 3.1.0 até 3.1.7, 
    * 4.0.0 até 4.0.10
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.


#### Disponibilizado novo parâmetro para permitir o envio de apenas os documentos pendentes no destinatário

Adicionado novo parâmetro de configuração EnviarApenasComponentesDigitaisPendentes em ConfiguracaoModPEN.php para possibilitar a troca de processos enviando apenas o conjunto de documentos pendentes na instituição destinatária, e não todos os documentos do processo. Esta funcionalidade é útil nos cenários de envio e devolução de processos grandes, diminuindo consideravelmente a velocidade da transmissão e evitando erros relacionados a problemas de validação de Hash. 

A configuração deste parâmetro exige a indicação explícita do Repositório e Unidade de destino, já que é necessário que o sistema destinatário também esteja utilizando esta versão do módulo ou outra superior para não gerar erros durante o recebimento dos processos.


#### Correção de erro de envio de documentos movimentados para outros processos (#228)

Corrigido falha ao realizar o trâmite de processos que possuam documentos que foram movidos para outros processos e depois retornados ao mesmo processo, mas em posição diferente. Neste cenário, o envio de processo falhava com indicação de erro ao salvar as informações nas tabelas de componentes digitais por duplicidade de chave primária. 


#### Melhoria de desempenho dos trâmites com o aumento do tamanho do bloco de particionamento de arquivos (#268)

Modificado o valor padrão dos blocos de dados de transmissão de dados para o Tramita.gov.br de 5Mbs para 50Mbs, aumentando a velocidade do envio e recebimento de processos e diminuindo o processamento de validação da integridade dos documentos.  


#### Correção de erro de múltiplos envios de processo por meio do Envio em Lote (#267)

Corrigido falha nas rotinas de processamento de envio de processos em lote que gerava inúmeras tentativas de transmissão de processos com falha em seus dados, gerando processamento desnecessário e gerando inúmeros registros no histórico de trâmites no Painel de Controle da instituição no Tramita.gov.br 


#### Correção de erro processando operação consultarHtmlVersao no envio em lote (#272)

Corrigido "Erro processando operação consultarHtmlVersao." quando realizado um trâmite de processo utilizando a funcionalidade de envio em lote. O erro ocorre quando o sistema está configurado para utilização de protocolo HTTPS e o processo contém documentos internos e e-mails. 


#### Adicionado mensagem de validação para evitar a configuração de Tipo de Processo padrão sem Assunto vinculado (#67)
    
Melhoria foi inserida na página de configuração do módulo para evitar a configuração de tipos de processos sem assunto vinculado, provocando erro no recebimento e recusa do processo por falta desta configuração.


#### Restringido exibição de botão 'Cancelar Tramitação Externa' somente para a unidade que envio processo (#126)

As versões anteriores do módulo permitiam, erroneamente, a qualquer unidade do sistema a possibilidade de cancelar o trâmite de um processo. A melhoria aplicada nesta versão permite que apenas a unidade que enviou o processo possa cancelar o trâmite externo.


#### Restrição de parâmetro "Unidade de Representação de Órgãos Externo" para não receber processos

Adicionado restrição na página de configuração do módulo para permitir apenas a escolha de "Unidade de Representação de Órgãos Externo" que não estejam disponíveis para envio de processos. Isto é necessário para evitar a escolha de uma unidade que esteja em pleno uso pela instituição e com usuários vinculados, indo contra as orientações descritas no manual e possibilitando que processos sejam recebidos automaticamente no estado fechado ou com possibilidade de realização de alterações indevidas no processo.


#### Adicionado melhorias no log de verificação da disponibilidade do Tramita.gov.br

A melhoria adicionada no log permitirá que a equipe de operações possa identificar, com precisão, qual a falha de conectividade está ocorrendo, principalmente por falhas relacionadas a falta de confiabilidade do servidor do SEI com os certificados digitais Let's Encrypt utilizados pela API do Tramita.gov.br


#### Tratamento para não recusar trâmite em caso de falha no registro do recibo (#215)


#### Correção de erro de validação de hash em processos contendo documento do tipo e-mail


#### Correção de erros de formatação nos campos da página envio de processos em lote (#200)



### Atualização de Versão

Para obter informações detalhadas sobre cada um dos passos de atualização, vide arquivo **ATUALIZACAO.md**.

#### Instruções

1. Baixar a última versão do módulo de instalação do sistema (arquivo `mod-sei-pen-[VERSÃO].zip`) localizado na página de [Releases do projeto MOD-SEI-PEN](https://github.com/spbgovbr/mod-sei-pen/releases), seção **Assets**. _Somente usuários autorizados previamente pela Coordenação-Geral do Processo Eletrônico Nacional podem ter acesso às versões._

2. Fazer backup dos diretórios "sei", "sip" e "infra" do servidor web;

3. Descompactar o pacote de instalação `mod-sei-pen-[VERSÃO].zip`;

4. Copiar os diretórios descompactados "sei", "sip" para os servidores, sobrescrevendo os arquivos existentes;

5. Executar o script de instalação/atualização `sei_atualizar_versao_modulo_pen.php` do módulo para o SEI localizado no diretório `sei/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

6. Executar o script de instalação/atualização `sip_atualizar_versao_modulo_pen.php` do módulo para o SIP localizado no diretório `sip/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

7. Verificar a correta instalação e configuração do módulo

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 
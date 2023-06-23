# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.3.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

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


#### Disponibilizado novo parâmetro para permitir o envio de apenas os documentos pendentes no destinatário, evitando erros relacionados à hash inválido 

O tratamento de erros de trâmites relacionado a validação de hash por meio de um parâmetro de configuração  EnviarApenasComponentesDigitaisPendentes em ConfiguracaoModPEN.php (Ler seção de parâmetros no README para maiores detalhes);


#### Correção de erro de envio de documentos movimentados para outros processos (#228)

O erro que impossibilita o envio de processo com documento externo movimentado para outro processo e depois retornado ao original( documento duplicado, sendo um deles apenas uma referência a movimentação) foi corrigido.


#### Melhoria de desempenho dos trâmites com o aumento do tamanho do bloco de particionamento de arquivos (#268)

O tamanho do bloco de dados foi aumentado de 5 para 50Mbs, seja para o envio ou recebimento de processos por meio do Tramita.GOV.BR. Dessa forma, contribuindo para celeridade no trâmite de processos.


#### Correção de erro de múltiplos envios de processo por meio do Envio em Lote (#267)

A correção do erro da funcionalidade Envio Lote que possibilitava o envio n-plicados do mesmo processo para o Tramita.GOV.BR.


#### Correção de erro processando operação consultarHtmlVersao no envio em lote (#272)

Corrigido "Erro processando operação consultarHtmlVersao." quando realizado um trâmite de processo utilizando a funcionalidade de envio em lote. O erro ocorre quando o sistema está configurado para utilização de protocolo HTTPS e o processo contém documentos internos e e-mails.


#### Adicionado mensagem de validação para evitar a configuração de Tipo de Processo padrão sem Assunto vinculado (#67)
    
"Na funcionalidade Processo Eletrônico Nacional --> Parâmetros de Configuração do Módulo de Tramitação PEN não é possível escolher um Tipo de Processo Externo sem assunto vinculado. Uma validação foi incluída ao salvar a alteração.

A ausência de assunto desse tipo de processo causava a recusa do trâmite. Portanto, com essa correção o processo não será recusado pelo seguinte motivo:  Nenhum assunto foi informado."


#### Exibição do botão 'Cancelar Tramitação Externa' somente para a unidade que enviou o processo (#126)

Apenas a unidade responsável pelo envio do processo para o Tramita.GO.BR poderá cancelar o seu trâmite.


#### Restrição do parâmetro "Unidade SEI para Representação de Órgãos Externos" para exibir somente unidades habilitadas para receber processos

Adicionada validação na página de configuração do módulo para permitir apenas a escolha de unidades disponíveis para o envio de processos. As unidades com o campo 'Disponível para Envio de Processos' desativado não podem mais ser selecionadas no campo 'Unidade SEI para Representação de Órgãos Externos'.


#### Detalhamento no log de verificação da disponibilidade do Tramita.gov.br (#270)

O log foi melhorado para contemplar mais detalhes e facilitar o entendimento do problema antes da abertura de chamado para a Central de Atendimento.


#### Tratamento para não recusar trâmite em caso de falha no registro do recibo (#215)

O trâmite não será mais recusado em caso de falha no registro do recibo.


#### Correção de erro de validação de hash em processos contendo documento do tipo e-mail

A correção do erro de validação de hash para processos com e-mails, no qual o envio externo ficava em situação "Validação em Informações do Processo" e não chegava ao status 1. 

OBS: Soluciona os casos em que o processo foi enviado na versão 3.X.X e posteriormente na 4.X.X ocorreu o erro acima. 


#### Correção de erros de formatação nos campos da página envio de processos em lote (#200)

Melhoria de Usabilidade da Tela Envio Externo de Processo em Lote, a qual é acessada por meio do Controle de Processos.


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

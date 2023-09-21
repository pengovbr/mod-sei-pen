# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.3.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
    * 3.1.0 até 3.1.7, 
    * 4.0.0 até 4.0.11
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.


#### Quando não existe o mapeamento de tipo de documento estava sendo usado o primeiro valor da tabela e não o valor padrão (#309) 

Agora o módulo vai usar o valor padrão no envio e, caso não exista um valor padrão, vai ser usado o primeiro valor da tabela de relacionamento.

#### Detalhamento no log de verificação da disponibilidade do Tramita.gov.br (#270)

O log foi melhorado para contemplar mais detalhes e facilitar o entendimento do problema antes da abertura de chamado para a Central de Atendimento.


#### Antigo log 'Número de documentos do processo não confere com o registrado nos dados do processo no enviado externamente' foi alterado [commit](https://github.com/supergovbr/mod-sei-pen/commit/238c1d831add25e0cd5d45a9ab97c21c80479592)

Novo log agora consta com a quantidade encontrada e listagem dos documentos além do metadado recebido. Mensagem antiga era: 'Número de documentos do processo não confere com o registrado nos dados do processo no enviado externamente'


#### Deixa log mais verboso ao dar falha no recebimento de recibo tanto de envio como de recebimento [commit](https://github.com/supergovbr/mod-sei-pen/commit/a971c6f2dead2898c90cf3f038fdf3942632addb)

A mensagem anterior era 'Falha no recebimento de recibo de trâmite'
Agora a mensagem terá mais detalhes.

#### Ao receber arquivos ocorre erro de division by zero pela variável numParamTamMaxDocumentoMb estar com valor NULL. (#311)

Havia change de ocorrer uma divisão por zero devido a falha de obtenção de valor da variável 'numParamTamMaxDocumentoMb'. Agora o tratamento será por padrão 50 megas caso ocorra erro ao obter a variável.

#### Inclui mais log na verificação do certificado público e privado [commit](https://github.com/supergovbr/mod-sei-pen/commit/9463d50af299c3167ce02864c62e466210ba75a2)

Ao tentar verificar a validade do certificado SSL caso ocorra algum erro é exibido os detalhes mais verbosos. Mensagem antiga era: 'Chave pública/privada do certificado digital de autenticação no Barramento do PEN não pode ser localizada em'

#### I nclui mais log no uso do Pendências [commit](https://github.com/supergovbr/mod-sei-pen/commit/f9c77c642865d7943dc24ad83f09bdd2024e3007)

Ao obter as pendências do trâmite vai trazer mais detalhes caso ocorra um erro. Mensagem antiga: 'Erro na requisição do serviço de monitoramento de pendências'

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

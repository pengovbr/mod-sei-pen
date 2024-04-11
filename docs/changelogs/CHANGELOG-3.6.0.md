# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.6.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
  * 4.0.0 até 4.0.12,
  * 4.1.1.
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### Integração do módulo com a base de dados em PostgreSQL do sistema SEI (#395)

A partir dessa versão, o módulo é compatível com o banco de dados PostgreSQL na versão XPTO. 

#### Nova Funcionalidade - Mapeamento de Envio Parcial (#344)

O Mapeamento de Envio Parcial a partir dessa versão é realizado pela própria interface da aplicação, sem necessidade de que a área técnica de tecnologia da informação realize a configuração em um arquivo no servidor do sistema e com isso os gestores de Negócio tem maior autonomia para realizar as configurações necessárias para o envio parcial. 

#### Blocos de Trâmite Externo (#245, #246,#248, #306, #247, #249)

O Bloco de Envio Externo é a evolução natural da funcionalidade Envio em lote, a qual foi descontinuada a partir da versão 3.6.0.  O novo tipo de bloco utiliza a mesma lógica estabelecida pelos outros tipos de blocos, claro respeitando a respeitando a particularidades de cada um.  Com a nova funcionalidade, o usuário pode criar blocos de até 100 processos para envio a outro órgão, resultando em ganho de produtividade na tramitação de processos por meio do Tramita GOV.BR. 

#### Agrupar funcionalidades do Tramita GOV.BR em um único item de menu (#289)

No menu principal foi criado o item 'Tramita GOV.BR' para agrupar as seguintes funcionalidades: 

'Blocos de Trâmite Externo'; 
'Processos tramitados Externamente'; 
'Processos tramitados em Bloco'. 

#### Alterar o nome do Menu Processo Eletrônico Nacional para Tramita GOV.BR (#237)

Alterado o nome do menu 'Administração -> Processo Eletrônico Nacional' para 'Administração -> Tramita GOV.BR'.

#### Processo enviado duplicado em uma fila de processamento, através de um Bloco Externo. (#473)

Corrige bug que ao rodar o script de monitoramento (agendamento), pode ocorrer de um processo ser processado mais de uma vez na fila e, com isso, favorecer o surgimento de erros de tramitação, erros de recusa (por duplicidade), ou um processo ficar aberto em dois locais ao mesmo tempo.

#### Erro ao tentar usa o SEI quando o módulo não está devidamente instalado (#455)

O erro `Table 'sei.md_pen_protocolo' doesn't exist não é mais apresentado para o usuário se o módulo do tramita estiver instalado mas não foi executado o script de atualização. 

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

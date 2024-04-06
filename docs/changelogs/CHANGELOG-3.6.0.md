# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.5.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
    * 3.1.0 até 3.1.7, 
    * 4.0.0 até 4.1.11

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### Integração do módulo com a base de dados em PostgreSQL do sistema SEI (#395)

Suporte para o banco de dados PostgreSQL na versão XPTO.

#### Nova Funcionalidade - Mapeamento de Envio Parcial (#344)

O gestor do tramita pode agora configurar o mapeamento de envio parcial através do menu 'Administração -> Tramita GOV.BR -> Mapeamento de Envio Parcial'. O arquivo de configuração será usado para povooar o primeiro mapeamento na atualização do módulo. Depois da atualização não é mais utilizado o arquivo de configuração para envio parcial.

#### Testes automatizados para Cadastro de Bloco de Trâmite Externo (#292)

Criação de testes funcionais para bloco de trâmite externo.

#### Execução de rodadas de testes completos referentes do blocos de tramite externo (#433)

Execução de testes funcionais completo para os diversos banco de dados e versões do SEI com uso de fixtures.

#### Blocos de Trâmite Externo

##### Tela de Listagem de Blocos de Trâmite Externo (#245)

O usuário pode listar blocos de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo'.

##### Cadastrar/Alterar Bloco de Trâmite Externo (#246)

O usuário para cadastrar e alterar blocos de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo'.

##### Visualizar processos do bloco de trâmite externo (#248)

O usuário pode listar os processos do bloco de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo' clicando no ícone 'Visualizar Processos'.

##### Adicionar o processo no bloco (#306)

O usuário pode listar os processos do bloco de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo'.

##### Excluir Bloco de Trâmite Externo (#247)

O usuário pode listar os processos do bloco de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo' clicando no ícone 'Excluir bloco'.

##### Tramitar bloco externamente (#249)

O usuário pode enviar um bloco de processos para o tramita no item 'Tramita GOV.BR -> Blocos de Trâmite Externo'.

#### Agrupar funcionalidades do Tramita.gov.br em um único item de menu (#289)

Criado um item de menu com o nome 'Tramita GOV.BR' e agrupado o 'Blocos de Trâmite Externo', 'Processos tramitados Externamente' e 'Processos tramitados em Lote'. 

#### Alterar o nome do Menu Processo Eletrônico Nacional para Tramita GOV.BR (#237)

Alterado o nome do menu 'Administração -> Processo Eletrônico Nacional' para 'Administração -> Tramita GOV.BR'.

#### Processo Desatualizado - Ausência de Assinatura (#333)

Não será permitida a inclusão de uma nova assinatura em documentos já tramitados via Tramita GOV.BR e ao tentar assinar um documento já tramitado externamente, o usuário deverá ser informado que essa ação não é permitida conforme mensagem que segue:
"Prezado(a) usuário(a) esse documento já foi tramitado externamente via Tramita GOV.BR. Por esse motivo, este documento não pode receber uma nova assinatura."

#### Processo enviado duplicado em uma fila de processamento, através de um bloco externo (lote). (#473)

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

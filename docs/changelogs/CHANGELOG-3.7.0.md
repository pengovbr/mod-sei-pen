# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.7.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
  * 4.0.0 até 4.0.12,
  * 4.1.1, 4.1.2, 4.1.3 e 4.1.4
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### **CORREÇÕES DE PROBLEMAS**

#### Nesta versão, foram corrigidos os seguintes erros:

* Tramitação de processos com caracteres inválidos no assunto [#328](https://github.com/pengovbr/mod-sei-pen/issues/328);

* **Atualização do script de instalação/atualização:** a variável  "EnviarApenasComponentesDigitaisPendentes" é inserida automaticamente no arquivo de configuração do módulo. Essa variável tornou-se obrigatória a partir desta versão [#527](https://github.com/pengovbr/mod-sei-pen/issues/527);

* Implementação uma regra que impede a inclusão de novas assinaturas em documentos já tramitados, evitando problemas de hash nos trâmites [#333](https://github.com/pengovbr/mod-sei-pen/issues/333);

* **Visualização de Hipóteses Legais:** Hipóteses Legais desativadas no Portal de Administração não serão exibidas ao criar um novo mapeamento [#354](https://github.com/pengovbr/mod-sei-pen/issues/354);

* **Acesso ao envio por bloco:** o script de atualização do SIP foi alterado para assegurar que as funcionalidades de envio por bloco estejam disponíveis para usuários com permissões de perfil Básico [#523](https://github.com/pengovbr/mod-sei-pen/issues/523) e [#542](https://github.com/pengovbr/mod-sei-pen/issues/542);

#### **MELHORIAS**

#### As melhorias implementadas nesta versão incluem:

* **Verificação de tamanho de componentes digitais:** antes de iniciar o download, o sistema agora verifica, pelos metadados, se o tamanho das componentes digitais é aceito no SPE de destino [#155](https://github.com/pengovbr/mod-sei-pen/issues/155);

* Mapeamento Automático de Hipóteses Legais [#350](https://github.com/pengovbr/mod-sei-pen/issues/350);

* Mensagem aprimorada para tentativas de envio de trâmites por unidades incorretamente mapeadas [#481](https://github.com/pengovbr/mod-sei-pen/issues/481);

* **Registro no histórico do processo:** alterações no tipo de processo agora são registradas no histórico [#416](https://github.com/pengovbr/mod-sei-pen/issues/416);

* **Limitação de repositórios e unidades:** Gestores de Protocolo agora podem limitar quais repositórios e unidades aparecem na lista de destino de cada unidade mapeada [#196](https://github.com/pengovbr/mod-sei-pen/issues/196);

* Remoção do ícone de organograma na tela de envio de trâmite externo e envio de bloco de trâmite exteno [#540](https://github.com/pengovbr/mod-sei-pen/issues/540);

* **Ajuste no menu:** Tela _Processos Tramitados em Lote_ removida e melhoria no layout da tela _Processos em Tramitação Externa_ [#475](https://github.com/pengovbr/mod-sei-pen/issues/475);

* **Blocos de Trâmite Externo:**
  - Mensagem aprimirada após o envio de um bloco [#435](https://github.com/pengovbr/mod-sei-pen/issues/435);
  - Novo botão para remover processo de bloco e reformulação das regras negociais ([#496](https://github.com/pengovbr/mod-sei-pen/issues/496), [#506](https://github.com/pengovbr/mod-sei-pen/issues/506), [#536](https://github.com/pengovbr/mod-sei-pen/issues/536), [#610](https://github.com/pengovbr/mod-sei-pen/issues/610), [#627](https://github.com/pengovbr/mod-sei-pen/issues/627), [#629](https://github.com/pengovbr/mod-sei-pen/issues/629), [#635](https://github.com/pengovbr/mod-sei-pen/issues/635), [#648](https://github.com/pengovbr/mod-sei-pen/issues/648) e [#696](https://github.com/pengovbr/mod-sei-pen/issues/696)), incluindo:
    + Blocos criados em versões anteriores não serão recuperados após atualização;
    + Blocos estarão visíveis apenas para unidades onde foram criados e que estiverem devidamente mapeadas;
    + Processos incluídos em bloco, e não tramitados, devem ser removidos do bloco antes de tramitarem individualmente;
    + Reclassificação dos Estados dos blocos conforme a Situação dos processos tramitados.";

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

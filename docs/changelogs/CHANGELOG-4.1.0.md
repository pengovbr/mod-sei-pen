# NOTAS DE VERSÃO MOD-SEI-PEN (versão 4.1.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
  * SEI 5.0.0, 5.0.1, 5.0.2, 5.0.3, 5.0.4 e 5.1.0

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **UPGRADE.md** - Procedimento específicos para atualização de uma versão anterior

## Atenção: esta versão executa migração de dados

O script de atualização desta versão move os anexos de documentos internos da tabela `anexo` do SEI para a tabela `md_pen_anexo_documento` do módulo, com árvore de arquivos própria em `RepositorioArquivos/mod-pen/AAAA/MM/DD/`. A migração é processada em lotes, é retomável e registra o progresso em log.

Antes de atualizar:

* **Faça backup do banco de dados e do repositório de arquivos.** A migração remove a linha original da tabela `anexo` após gravar o novo arquivo;
* **Execute a atualização com o sistema fora do ar.** O tempo é proporcional ao volume de anexos do órgão;
* **Reserve janela compatível com o volume da base.** Em ambiente de teste, a migração processou cerca de 2 a 3 milissegundos por anexo;
* **Em instalações com Gearman**, pare os workers antes de atualizar e só religue depois que todos os nós de aplicação estiverem na nova versão. Consulte o **UPGRADE.md** para o procedimento detalhado;
* **Mantenha o agendador de tarefas do SEI parado durante toda a janela**, e desabilite o agendamento `AgendamentoRN::removerAquivosNaoUtilizados` antes de atualizar. Rotinas de limpeza de arquivos — tanto do SEI quanto do próprio módulo — atuam sobre os mesmos anexos que a migração está movendo. Mantê-las paradas durante o procedimento evita interferência. Reative o agendador após a conclusão da migração;

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### **NOVAS FUNCIONALIDADES / MELHORIAS**

#### Nesta versão, foram contempladas as seguintes melhorias:

* **Compatibilização do SEI 5.1.0 com o módulo do Tramita:** Libera a compatibilização do módulo do Tramita 4.1.0 com o SEI versão 5.1.0;

* **Erro ao duplicar processo tramitado:** Corrige o erro "Documento não pode receber anexos" ao duplicar um processo que já foi tramitado pelo Tramita GOV.BR. Os anexos de documentos internos passam a ser mantidos em tabela e repositório próprios do módulo, preservando o documento imutável recebido do barramento. [#1127](https://github.com/pengovbr/mod-sei-pen/issues/1127);

* **Desanexação de processo já tramitado:** Permite desanexar um processo de outro processo quando já houve tramitação externa, com as validações necessárias para preservar a integridade do trâmite. [#1128](https://github.com/pengovbr/mod-sei-pen/issues/1128), [#1210](https://github.com/pengovbr/mod-sei-pen/issues/1210);

* **Restrição de mapeamento duplicado de tipo de documento:** Impede o cadastro de mais de um mapeamento de envio para o mesmo tipo de documento. A atualização também remove as duplicidades já existentes na base antes de aplicar a restrição. [#1213](https://github.com/pengovbr/mod-sei-pen/issues/1213), [#1207](https://github.com/pengovbr/mod-sei-pen/issues/1207);

* **Influência do parâmetro md_pen_tramita_em_bloco no acesso ao SEI:** Ajusta o comportamento do parâmetro de tramitação em bloco para que não interfira no acesso ao sistema. [#1109](https://github.com/pengovbr/mod-sei-pen/issues/1109);

* **Mensagem na exclusão de mapeamento de envio parcial:** Corrige a mensagem exibida na confirmação de exclusão de mapeamento de envio parcial. [#1108](https://github.com/pengovbr/mod-sei-pen/issues/1108);

* **Assinatura sem nome ou cargo informado:** Trata os casos de assinatura com nome e/ou cargo ausentes, substituindo a mensagem de erro genérica por informação compreensível ao usuário. [#1177](https://github.com/pengovbr/mod-sei-pen/issues/1177), [#1183](https://github.com/pengovbr/mod-sei-pen/issues/1183), [#1193](https://github.com/pengovbr/mod-sei-pen/issues/1193);

#### **CORREÇÕES DE PROBLEMAS**

#### Nesta versão, foram corrigidos os seguintes erros:

* **Erro ao enviar bloco de processo externo:** Corrige falha identificada no envio de bloco contendo processo externo. [#1202](https://github.com/pengovbr/mod-sei-pen/issues/1202);

* **Erro na tramitação de processos com documentos grandes:** Corrige falha na tramitação de processos que contêm documentos de grande volume. [#1194](https://github.com/pengovbr/mod-sei-pen/issues/1194);

* **Erro ORA-00932 em base Oracle:** Corrige o erro "ORA-00932: inconsistent datatypes: expected - got CLOB" durante a consulta de componentes digitais. [#1137](https://github.com/pengovbr/mod-sei-pen/issues/1137);

* **Lock recorrente no banco de dados:** Corrige atualização repetida de registro que provocava bloqueio no banco de dados durante o processamento de trâmites. [#1041](https://github.com/pengovbr/mod-sei-pen/issues/1041);

* **Falha na limpeza da lixeira ao excluir documento definitivamente:** A chave estrangeira entre `md_pen_componente_digital` e `anexo` passa a usar `ON DELETE SET NULL`, permitindo que a tarefa agendada de limpeza da lixeira do SEI conclua a exclusão definitiva. [#1217](https://github.com/pengovbr/mod-sei-pen/issues/1217);

* **Processamento assíncrono de pendências pelo Gearman:** Corrige o tempo limite de registro das funções no Gearman, unifica o despacho das pendências em uma única função e trata a porta do servidor quando informada em branco ou como texto, evitando falha na verificação da instalação. [#1180](https://github.com/pengovbr/mod-sei-pen/issues/1180);

* **Controle de acesso e tratamento de conteúdo nas telas do módulo:** Reforça a verificação de permissão nas rotas de expedição e de mapeamento e aplica tratamento ao conteúdo dinâmico exibido nas telas de administração do módulo, melhorando, assim, a segurança do módulo.


* **Preservação de arquivos migrados:** Ajusta o tratamento dos arquivos já migrados para que não sejam corrompidos em atualizações subsequentes. [#1187](https://github.com/pengovbr/mod-sei-pen/issues/1187);


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

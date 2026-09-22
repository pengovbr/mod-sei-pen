# NOTAS DE VERSÃO MOD-SEI-PEN (versão 4.1.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Correção pós-publicação (pacote 4.1.0-fix)

> [!IMPORTANT]
> No **Oracle**, a atualização podia abortar com `ORA-01408: such column list already indexed`
> ao criar o índice único de `id_serie` em `md_pen_rel_doc_map_enviado`, antes de qualquer
> migração de anexo. O pacote **4.1.0-fix** corrige isso.
>
> * **Se a sua atualização falhou com esse erro:** nenhum anexo foi migrado e a base não ficou
>   em estado misto. Basta atualizar com este pacote e executar o script novamente.
> * **Se a sua atualização concluiu com sucesso:** nenhuma ação é necessária.
>
> O número da versão do módulo permanece **4.1.0** — a correção não altera o esquema do banco
> nem o comportamento da migração de anexos. Para confirmar qual pacote está instalado, veja
> se este bloco existe no `NOTAS_VERSAO.md` da instalação.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
  * SEI 5.0.0, 5.0.1, 5.0.2, 5.0.3, 5.0.4 e 5.1.0

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **UPGRADE.md** - Procedimento específicos para atualização de uma versão anterior

## Atenção: esta versão executa migração de dados

O script de atualização desta versão cria três índices de apoio (dois em `md_pen_componente_digital` e um em `md_pen_processo_eletronico`) e move os anexos de documentos internos de processos com ao menos um trâmite concluído (situação 6) da tabela `anexo` do SEI para a tabela `md_pen_anexo_documento` do módulo, com árvore de arquivos própria em `RepositorioArquivos/mod-pen/AAAA/MM/DD/`. A migração é processada em lotes, é retomável e registra o progresso em log.

Antes de atualizar:

* **Faça backup do banco de dados e do repositório de arquivos.** A migração remove a linha original da tabela `anexo` após gravar o novo arquivo;
* **Execute a atualização com o sistema fora do ar.** O tempo é proporcional ao volume de anexos do órgão;
* **Reserve janela compatível com o volume da base.** Em ambiente de teste, com 2 milhões de anexos, a migração processou de 2,9 a 7,0 milissegundos por anexo, conforme o banco. **O custo por anexo cresce com o tamanho da base** no Oracle e no PostgreSQL: dobrar o volume aproximadamente dobra o tempo de cada lote. Meça no volume real antes de definir a janela;
* **Em instalações com Gearman**, pare os workers antes de atualizar e só religue depois que todos os nós de aplicação estiverem na nova versão. Consulte o **UPGRADE.md** para o procedimento detalhado;
* **Execute a verificação prévia dos anexos antes da migração.** O script `verifica_anexos_migracao_modulo_pen.php`, em `sei/scripts/mod-pen/`, lista os anexos cujos arquivos estão ausentes, ilegíveis ou corrompidos. Esses anexos são ignorados pela migração — permanecem em `anexo`, e a migração grava a lista deles em arquivo, com id e motivo —, mas convém conhecê-los antes da janela. É somente leitura e pode rodar com o sistema no ar. Consulte o **UPGRADE.md**;
* **Ao final, confira se algum anexo foi ignorado.** A migração não interrompe por causa de arquivo ausente, ilegível ou corrompido: ela ignora o anexo, que permanece em `anexo`, e segue. A última linha da saída informa quantos foram e o caminho do arquivo com a lista (`<tmp>/migracao-anexos-ignorados-AAAAMMDD-HHMMSS-<pid>.log`), com id, caminho e motivo de cada um. Guarde esse arquivo antes de liberar o servidor: esses anexos precisam de tratamento;
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

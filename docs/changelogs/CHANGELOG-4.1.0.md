# NOTAS DE VERSÃO MOD-SEI-PEN (versão 4.1.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
  * SEI 5.0.0, 5.0.1, 5.0.2, 5.0.3, 5.0.4 e 5.1.0

> [!NOTE]  
> Os novos endereços para integração do módulo com o Tramita GOV.BR são os seguintes:
> **Homologação**: https://homolog.api.processoeletronico.gov.br/interoperabilidade/rest/v4/
> **Produção**: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/rest/v4/
> **Se o endereço não for modificado durante a instalação desta versão, o módulo não funcionará corretamente.**

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


### Erros Corrigidos

* **Influência do parâmetro no acesso ao SEI (#1096)** 
Corrigido o erro que bloqueava completamente o acesso ao SEI para usuários que não possuíam o parâmetro `md_pen_tramita_em_bloco` configurado no perfil.

* **Mensagem ao excluir mapeamento de envio parcial (#1099)** 
A mensagem de confirmação de exclusão foi ajustada para exibir o nome correto da unidade, e não as informações do repositório.

* **Inativação de tipo de processo no Oracle (#1120)** 
Resolvido um erro específico no banco de dados Oracle que impedia os usuários de inativarem tipos de processo.

* **Erro ao duplicar processo tramitado (#1127)** 
Consertada a falha que exibia uma mensagem de erro na tela sempre que o usuário tentava duplicar um processo que já havia tramitado. Agora o módulo apresenta o seguinte comportamento:

> Processo Bloqueado:  a funcionalidade não é apresentada para o usuário;
   Processo não Bloqueado: a funcionalidade é exibida.

* **Tramitação de processos com documentos grandes (#1180)** 
Corrigida a falha que causava a recusa frequente na conclusão do envio de processos com arquivos muito grandes.

* **Erro ao enviar bloco de processo externo (#1198)** 
Solucionado o erro que travava os blocos de processos externos durante a tentativa de envio, impedindo o cancelamento ou a realocação do processo.

* **Duplicidade na consulta de tipo de documentos (#1207)** 
Cada tipo de documento do SEI precisa ter uma única correspondência no módulo. Na hora de enviar o processo, o sistema busca essa correspondência; porém, quando ele encontrava mais de uma opção cadastrada, não sabia qual escolher, abortava o envio e exibia o erro na tela.

* **Limpeza da lixeira do SEI (#1211)** 
Resolvido o problema que impedia a rotina automática de limpeza da lixeira do SEI de excluir definitivamente documentos cancelados de processos tramitados pela plataforma Tramita GOVBR.

* **Alteração do parâmetro SEI_FEDERACAO_NUMERO_PROCESSO após recebimento de trâmite (#1041)** 
Foi corrigido o erro em que o parâmetro SEI_FEDERACAO_NUMERO_PROCESSO é setado para o valor "0", ainda que configurado com esse valor, gerando assim um lock no banco ao atualizar o registro, e por consequência gerava uma recusa no recebimento do processo no SEI.

### Melhorias

* **Desanexação de processos já tramitados (#1114)** 
O sistema passou a bloquear a desanexação de processos após a tramitação para evitar falhas na validação de segurança (hash) em envios futuros. Agora a seguinte mensagem é apresentada para o usuário, após a tentativa de desanexação:

>Não é possível desanexar o processo [Nº] do processo [Nº], pois já houve tramitação via Tramita GOV.BR.
 
* **Clareza na mensagem de erro de assinatura (#1174)**
O texto da mensagem exibida quando falta o nome ou cargo do assinante foi alterado para ser mais claro e orientar melhor o usuário sobre o que precisa ser tratado. Agora a seguinte mensagem é apresentada para o usuário: 

>Não foi adicionado o nome e nem o tratamento/cargo do assinante no documento [Identificador do documento] de ordem [Nº da ordem do documento]. Por favor, corrija e realize uma nova tentativa de envio.
OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro.

* **Compatibilidade com o SEI 5.1.0 (#1175)**
Agora o módulo é compatível com a versão 5.1.0 do SEI.

* **Preenchimento de dados de assinatura para SEI 5.1.0 (#1178)** 
O módulo agora preenche automaticamente os metadados Cargo e Nome com o texto "Informação inexistente" em assinaturas feitas em versões anteriores ao SEI 5.1.0, evitando que os processos sejam recusados. Caso os metadados não sejam preenchidos, a recusa da #1174 será emitida pelo sistema de destino.

* **Segurança na migração de anexos (#1186)** 
O roteiro técnico de migração de arquivos foi aprimorado para não deletar o arquivo original até que a operação seja totalmente concluída, evitando a perda de dados caso aconteça alguma falha no meio do processo.

* **Controle de acesso e tratamento de conteúdo nas telas do módulo:** 
Reforça a verificação de permissão nas rotas de expedição e de mapeamento e aplica tratamento ao conteúdo dinâmico exibido nas telas de administração do módulo, melhorando, assim, a segurança do módulo. 

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

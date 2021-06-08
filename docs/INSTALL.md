# Manual de Instalação do Módulo de Integração do Processo Eletrônico Nacional - PEN

O objetivo deste documento é descrever os procedimento para realizar a INSTALAÇÃO INICIAL do Módulo de Integração com o Barramento de Serviços do PEN (**mod-sei-pen**) no Sistema Eletrônico de Informações (SEI).

**ATENÇÃO: Caso o módulo já se encontre instalado em uma versão anterior, siga as instruções detalhadas de atualização no documento ATUALIZACAO.md presente no arquivo de distribuição do módulo (mod-sei-pen-VERSAO.zip)**

O módulo **PEN** é o responsável por integrar o Sistema Eletrônico de Informações - SEI à plataforma de interoperabilidade do Processo Eletrônico Nacional - PEN. Este projeto tem como objetivo interligar todos os sistema de processo eletrônico do Poder Executivo Federal a fim de proporcionar a troca de documentos oficiais de forma rápida, simplificada e segura.

A utilização deste módulo adicionará novas funcionalidades ao SEI, permitindo, entre outros:
 - Enviar e receber processos administrativos de outras instituições
 - Acompanhar a relação de processos em trâmite externo
 
Para maiores informações sobre o Barramento de Serviços e o PEN, acesse https://www.gov.br/economia/pt-br/assuntos/processo-eletronico-nacional/assuntos/processo-eletronico-nacional-pen.

Este documento está estruturado nas seguintes seções:

1. **[Instalação](#instalação)**:
Procedimentos destinados à Equipe Técnica responsáveis pela instalação do módulo nos servidores web e atualização do banco de dados.

2. **[Configuração](#configuração)**:
Procedimentos destinados ao Administradores do SEI responsáveis pela configuração do módulo através da funcionalidades de administração do sistema.

3. **[Configurações Técnicas Adicionais](#configuracoes-técnicas-adicionais)**:
Esta seção apresenta algumas configurações adicionais do módulo do Barramento de Serviços do PEN que não são obrigatórias para o funcionamento da integração, mas adicionam maior segurança, confiabilidade e desempenho ao módulo.

4. **[Suporte](#suporte)**:
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

5. **[Problemas Conhecidos](#problemas-conhecidos)**:
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

---

## 1. INSTALAÇÃO

Esta seção descreve os passos obrigatórios para **INSTALAÇÃO** do **```**mod-sei-pen**```**.  
Todos os itens descritos nesta seção são destinados à equipe de tecnologia da informação, responsáveis pela execução dos procedimentos técnicos de instalação e manutenção da infraestrutura do SEI.

### Pré-requisitos
 - **SEI versão 3.1.x ou superior instalada**;
 - Usuário de acesso ao banco de dados do SEI e SIP com permissões para criar novas estruturas no banco de dados
 - Certificado Digital de autenticação de sistema no Barramento do PEN emitido pela equipe do Processo Eletrônico Nacional após aprovação do Comitê Gestor de Protocolo.
  
Para iniciar os procedimentos de configuração, será necessário registrar no **Barramento de Serviços do PEN** às unidades administrativas que poderão realizar o envio e recebimento de processos externo no SEI. Este procedimento precisa ser realizado pelo **Gestor de Protocolo**, previamente habilitado no portal do **Barramento de Serviços**. Lembrando que os testes devem ser feitos primeiro em ambiente de homologação para, posteriormente, a utilização em produção ser liberada. Para solicitação de acesso aos ambientes, acesse os seguintes endereços:

 - **Homologação:** [https://homolog.gestaopen.processoeletronico.gov.br/](https://homolog.gestaopen.processoeletronico.gov.br/solicitarCadastroComite "HOMOLOGAÇÃO: Portal de Administração Barramento de Serviços do PEN - Cadastro de Comitê")
 - **Produção:** [https://gestaopen.processoeletronico.gov.br/](https://gestaopen.processoeletronico.gov.br/solicitarCadastroComite "PRODUÇÃO: Portal de Administração Barramento de Serviços do PEN - Cadastro de Comitê")

Para maiores informações, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço https://portaldeservicos.economia.gov.br/citsmart/login/login.load.

### Procedimentos:

### 1.1 Fazer backup dos bancos de dados do SEI, SIP e dos arquivos de configuração do sistema.

Todos os procedimentos de manutenção do sistema devem ser precedidos de backup completo de todo o sistema a fim de possibilitar a sua recuperação em caso de falha. A rotina de instalação descrita abaixo atualiza tanto o banco de dados, como os arquivos pré-instalados do módulo e, por isto, todas estas informações precisam ser resguardadas.

---

### 1.2. Baixar o arquivo de distribuição do **mod-sei-pen**

Necessário realizar o _download_ do pacote de distribuição do módulo **mod-sei-pen** para instalação ou atualização do sistema SEI. O pacote de distribuição consiste em um arquivo zip com a denominação **mod-sei-pen-VERSAO**.zip e sua última versão pode ser encontrada em https://github.com/spbgovbr/mod-sei-pen/releases

---

### 1.3. Descompactar o pacote de instalação e atualizar os arquivos do sistema

Após realizar a descompactação do arquivo zip de instalação, será criada uma pasta contendo a seguinte estrutura:

```
/**mod-sei-pen**-VERSAO 
    /sei              # Arquivos do módulo posicionados corretamente dentro da estrutura do SEI
    /sip              # Arquivos do módulo posicionados corretamente dentro da estrutura do SIP
    INSTALACAO.md     # Instruções de instalação do **mod-sei-pen**
    ATUALIZACAO.md    # Instruções de atualização do **mod-sei-pen**    
    NOTAS_VERSAO.MD   # Registros de novidades, melhorias e correções desta versão
```

Importante enfatizar que os arquivos contidos dentro dos diretórios ```sei``` e ```sip``` não substituem nenhum código-fonte original do sistema. Eles apenas posicionam os arquivos do módulos nas pastas corretas de scripts, configurações e pasta de módulos, todos posicionados dentro de um diretório específico denominado mod-pen para deixar claro quais scripts fazem parte do módulo.

Os diretórios ```sei``` e ```sip``` descompactados acima devem ser mesclados com os diretórios originais através de uma cópia simples dos arquivos.

Observação: O termo curinga VERSAO deve ser substituído nas instruções abaixo pelo número de versão do módulo que está sendo instalado

```
$ cp /tmp/**mod-sei-pen**-VERSAO.zip <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
$ cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
$ unzip **mod-sei-pen**-VERSAO.zip
```
---

### 1.4.  Habilitar módulo **mod-sei-pen** no arquivo de configuração do SEI

Esta etapa é padrão para a instalação de qualquer módulo no SEI para que ele possa ser carregado junto com o sistema. Edite o arquivo **sei/config/ConfiguracaoSEI.php** para adicionar a referência ao módulo PEN na chave **[Modulos]** abaixo da chave **[SEI]**:    

```php
'SEI' => array(
    'URL' => ...,
    'Producao' => ...,
    'RepositorioArquivos' => ...,
    'Modulos' => array('PENIntegracao' => 'pen'),
    ),
```

Adicionar a referência ao módulo PEN na array da chave 'Modulos' indicada acima:

```php
'Modulos' => array('PENIntegracao' => 'pen')
```
---

### 1.5. Configurar os parâmetros do Módulo de Integração PEN

A instalação da nova versão do **mod-sei-pen** cria um arquivo de configuração específico para o módulo dentro da pasta de configuração do SEI (**<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/**). 

O arquivo de configuração padrão criado **ConfiguracaoModPEN.exemplo.php** vem com o sufixo **exemplo** justamente para não substituir o arquivo principal contendo as configurações vigentes do módulo.

Caso não exista o arquivo principal de configurações do módulo criado em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php**, renomeie o arquivo de exemplo para iniciar a parametrização da integração.

```
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/
mv ConfiguracaoModPEN.exemplo.php ConfiguracaoModPEN.php
```

Altere o arquivo de configuração específico do módulo em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php** e defina as configurações do módulo, conforme apresentado abaixo:

* **WebService**  
Endereço do Web Service principal de integração com o Barramento de Serviços do PEN. Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.api.processoeletronico.gov.br/interoperabilidade/soap/v2/
    * Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/


* **LocalizacaoCertificado**  
Localização completa do certificado digital utilizado para autenticação nos serviços do Barramento de Serviços do PEN. Os certificados digitais são disponibilizados pela equipe do Processo Eletrônico Nacional mediante aprovação do credenciamento da instituição. Verifique a seção [pré-requisitos](#pré-requisitos) para maiores informações.  
Necessário que o arquivo de certificado esteja localizado dentro da pasta de configurações do módulo:
```
Exemplo: <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/certificado.pem
```

* **SenhaCertificado**  
Senha do certificado digital necessário para a aplicação descriptografar e acessar a sua chave privada.

* **Gearman** _(opcional e altamente desejável)_  
Localização do servidor Gearman de gerenciamento de fila de processamento de tarefas do Barramento PEN.  
As mensagens recebidas do Barramento são organizadas em filas de tarefas e distribuídas entre os nós da aplicação para processamento coordenado. Caso este parâmetro não seja configurado ou o servidor do Gearman esteja indisponível, o processamento será feito diretamente pelo sistema na periodicidade definida no agendamento da tarefa _PENAgendamentoRN::processarTarefasPEN_.  
Veja [Processamento paralelo de processos com Gearman]((#processamento-paralelo-de-multiplos-processos-com-Gearman)) para maiores informações.

    * **Servidor**  
    *IP ou Hostname do servidor Gearman instalado*

    * **Porta**  
    *Porta utilizada para conexão ao servidor do Gearman. Valor padrão 4730*


* **NumeroTentativasErro** _(opcional)_
Quantidade de tentativas de requisição dos serviços do Barramento PEN antes que um erro possa ser lançado pela aplicação
Necessário para aumentar a resiliência da integração em contextos de instabilidade de rede. *Valor padrão: 3*

* **WebServicePendencias** _(opcional)_  
Endereço do Web Service de monitoramente de pendências de trâmite no Barramento de Serviços do PEN.
Configuração necessária somente quando o módulo é configurado para utilização conjunta com o Supervisor para monitorar ativamente todos os eventos de envio e recebimentos de processos enviados pelo Barramento de Serviços do PEN.  
Para maiores informações sobre como utilzar este recurso. Veja a seção [Conexão persistente com uso do Supervisor](#Conexão-persistente-com-uso-do-Supervisor) para maiores informações.  
Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.pendencias.processoeletronico.gov.br/
    * Produção: https://pendencias.conectagov.processoeletronico.gov.br/  

---

### 1.6. Atualizar a base de dados do SIP com as tabelas do **mod-sei-pen**

A atualização realizada no SIP não cria nenhuma tabela específica para o módulo, apenas é aplicada a criarção os recursos, permissões e menus de sistema utilizados pelo **mod-sei-pen**. Todos os novos recursos criados possuem o prefixo **pen_** para fácil localização pelas funcionalidades de gerenciamento de recursos do SIP.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php```

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

---

### 1.7. Atualizar a base de dados do SEI com as tabelas do **mod-sei-pen**

Nesta etapa é instalado/atualizado as tabelas de banco de dados vinculadas do **mod-sei-pen**. Todas estas tabelas possuem o prefixo **md_pen_** para organização e fácil localização no banco de dados.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php```

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

---

### 1.8. Configuração do relógio do servidores do SEI

O protocolo de comunicação implementado pelo PEN realiza a geração e assinatura digital de recibos de entrega e conclusão dos trâmites de processo. Para a correta geração dos recibos pelo módulo, é indispensável que todos os nós da aplicação estejam configurados com o serviço de sincronização de relógios oficial NTP.br.  

Este link pode ajudar a configurar conforme o SO utilizado: http://ntp.br/guia-linux-comum.php

---

### 1.9. Configuração da periodicidade do agendamento de tarefas do SEI

A partir da versão SEI 3.1.x, o agendamento de tarefas do sistema pode ser executado em uma periodicidade de minutos, o que não era possível em versões anteriores (SEI 3.0.X). 

Com esta melhoria no SEI, o módulo mod-sei-pen 2.0.0 passou a utilizar o próprio agendamento de tarefas do sistema para realizar a consulta de novos processos no Barramento de Serviços do PEN, simplificando consideravelmente os procedimentos de instalação e configuração do módulo. 

Para que o módulo possa funcionar corretamente com o agendamento de tarefas do SEI, é necessário revisar a configuração do serviço CRON do sistema para certificar que ele se encontra corretamente configurado para ser executado a cada minuto. Ou seja, seguindo o manual de instalação do SEI 3.1.X, a configuração do serviço CRON nos servidores responsáveis pela execução dos agendamentos do SEI deve estar semelhante ao mostrado abaixo:

*Fonte: Manual de Atualização do SEI - Versão 3.1, item 2*:
```
2. A periodicidade de execução dos agendamentos no SEI e SIP mudou para minuto (antes era de hora em hora). É necessário alterar os comandos na crontab de:

00 * * * * root /usr/bin/php -c /etc/php.ini /opt/sei/scripts/AgendamentoTarefa...
Para:
* * * * * root /usr/bin/php -c /etc/php.ini /opt/sei/scripts/AgendamentoTarefa... 
``` 

Portanto, a periodicidade do serviço CRON deve estar configurado como:

```
# ┌───────────── todos os minutos (0 - 59)
# │ ┌───────────── todas as horas horas (0 - 23)
# │ │ ┌───────────── todos os dias do mês (1 - 31)
# │ │ │ ┌───────────── todos os meses (1 - 12)
# │ │ │ │ ┌───────────── todos os dia da semana (0 - 6) (Domingo à Sábado)
# │ │ │ │ │
# │ │ │ │ │
# * * * * * <comando para execução>
``` 


---

### 1.10. Verificação e testes da instalação do módulo

A versão 2.0.0 do **mod-sei-pen** adiciona um novo script utilitário ao SEI para que seja realizada uma verificação de todos os passos da instalação, assim como as configurações aplicadas. Estas verificações funcionam como um diagnóstico do correto funcionamento do sistema.

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

O resultado esperado para uma correta instalação e configuração do módulo é este apresentado abaixo:

```
INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN**:
    - Arquivos do módulo posicionados corretamente
    - Módulo corretamente ativado no arquivo de configuracao do sistema
    - Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php
    - Verificada a compatibilidade do **mod-sei-pen** com a atual versão do SEI
    - Certificado digital localizado e corretamente configurado
    - Base de dados do SEI corretamente atualizada com a versão atual do **mod-sei-pen**
    - Conexão com o Barramento de Serviços do PEN realizada com sucesso
    - Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso

** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN** FINALIZADA COM SECESSO **
```

**ATENÇÃO !  
Outras configurações avançadas do módulo podem ser encontradas na seção [Outras Configurações Adicionais](#1_1_Outras_Configurações_Adicionais) descrita logo abaixo neste documento.**

---
---

## 2. CONFIGURAÇÕES

Esta seção descreve os passos de configuração do módulo de Integração do SEI com o Barramento de Serviços do PEN. Todos os itens descritos nesta seção são destinados aos Administradores do sistema SEI da instituição, responsáveis pela alteração de configurações gerais do sistema através do menu de administração do SEI (**SEI >> Administração >> Processo Eletrônico Nacional**)


### 2.1. Configurar os parâmetros do Módulo de Integração PEN
Acesse a funcionalidade **[SEI > Administração > Processo Eletrônico Nacional > Parâmetros de Configuração]** para configurar os parâmetros de funcionamento do módulo:  

#### Repositório de Estruturas:
*Identificador do repositório de origem do órgão na estrutura organizacional. Este identificador é informado para a instituição pela equipe do Processo Eletrônico Nacional no envio do pacote de integração.*   
Exemplo: Poder Executivo Federal - *Valor 1 (Código de identificação da estrutura organizacional do Poder Executivo Federal)*

#### Tipo de Processo Externo:
*Identificação do Tipo de Processo que será aplicado à todos os processos e documentos recebidos de outras instituições pelo Barramento de Serviços do PEN.*  

Como o recebimento é realizado de forma automática, o sistema precisa atribuir um Tipo de Processo padrão para o novo processo recebido. Com isto, sugerimos a criação de um tipo de processo específico para estes processos, permitindo a fácil identificação e reclassificação, caso necessário. Segue abaixo um exemplo de Tipo de Processo que pode ser criado para esta situação:

    Nome: Processo Recebido Externamente (a classificar) 
    Descrição: Processos recebidos de outras instituições 
    // O assunto deve ser definido juntamente com a área de documentação
    Sugestão de Assuntos: a classificar
    Níveis de Acesso Permitidos: Restrito e Público 
    Nível de Acesso Sugerido: Público 
    Processo único no órgão por usuário interessado: Não
    Interno do Sistema: Sim       


#### Unidade SEI para Representação de Órgãos Externos
*Identificação da unidade administrativa que representará órgãos e unidades externas nos históricos de andamento do processo. Esta configuração também é necessária para que o sistema possa aplicar corretamente as regras de restrição de modificação de dados cadastrais de processos e documentos criados por outras instituições.*

A unidade a ser definida neste parâmetro será utilizada internamente pelo módulo e não deverá ter acesso de nenhum usuário do sistema. Por isto, não deve ser utilizada uma unidade pré-existente da própria instituição, sendo recomendado a criação de uma nova unidade administrativa "virtual" no SIP para esta configuração.

Sugerimos que a criação uma nova unidade no SEI denominada "**EXTERNO - Unidade Externa**" para atribuição à este parâmetro do sistema. Lembrando que novas unidades devem ser criadas inicialmente no SIP (SIP > Unidades) e depois atribuídas à hierarquia de unidades do SEI (SIP > Hierarquias > Montar).

#### Envia E-mail de Notificação de Recebimento
*Indicação se o sistema irá enviar um e-mail de notificação alertando o recebimento de um novo processo para a unidade. Necessário que a unidade tenha um e-mail configurado em seu cadastro de contato.*


### 2.2. Mapeamento de unidades que poderão realizar o envio e recebimento de trâmites externos

Acesse a funcionalidade **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades]** para configurar as unidades administrativas do SEI com as respectivas unidades habilitadas no Portal do Processo Eletrônico Nacional para envio e recebimento de processos.

As unidades administrativas que estão habilitadas para envio e recebimento de processos são gerenciadas pela própria instituição no Portal do Processo Eletrônico Nacional. Veja seção [Pré-condições para utilização](#pré-condições)

Primeiro selecione a unidade administrativa do SEI no campo de seleção e depois digite o nome da unidade habilitada no Barramento e aperte ENTER para que seja realizada uma consulta às do cadastro da unidade habilitada no Barramento de Serviços do PEN.

---

### 2.3. Mapeamento de Tipos de Documentos do SEI com as Espécies Documentais do PEN

A partir da versão **mod-sei-pen** 2.0.0, os mapeamentos dos Tipos de Documentos do SEI são realizados de forma automática durante a instalação do módulo ou automaticamente através do agendamento de tarefas **PENAgendamentoRN::atualizarInformacoesPEN**. 

Caso seja necessário modificar o mapeamento pré-definido pelo sistema, a alteração pode ser realizada através da funcionalidade **Mapeamento de Tipos de Documentos** localizado em [SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos]

**Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

Este mapeamento precisa ser feito tanto para o envio de processos como para o recebimento, sendo necessário realizar a configuração através das duas funcionalidades apresentadas abaixo:

- SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > **Envio**
- SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > **Recebimento**


##### 2.3.1. Atribuição de Espécie Documental Padrão para Envio
A configuração de Espécie Documental Padrão para Envio define qual será o comportamento do sistema ao enviar processos que contenham Tipos de Documentos não mapeados previamente pelo Administrador. Neste caso, a espécie documental configurada será aplicada automaticamente, evitando que o trâmite seja cancelado pela falta desta configuração. 

O mapeamento de Espécie Documental Padrão para Envio deve ser feito através da funcionalidade:

    [SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > **Envio > Botão "Atribuir Espécie Padrão"**]

##### 2.3.2. Atribuição de Tipo de Documento Padrão para Recebimento
 A configuração de Tipo de Documento Padrão para Recebimento define qual será o comportamento do sistema ao receber processos que contenham Espécies Documentais não mapeadas previamente pelo Administrador. Neste caso, o tipo de documento configurado será aplicado automaticamente, evitando que o trâmite seja cancelado pela falta de mapeamento.

O mapeamento de Tipo de Documento Padrão para Recebimento deve ser feito através da funcionalidade:

    [SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > **Recebimento > Botão "Atribuir Tipo de Documento Padrão"**]

PS: Somente Tipos de Documento com aplicabilidade 'Externa' ou 'Interna e Externa' podem ser selecionados para esta configuração. 

---


### 2.4. Mapeamento de hipóteses legais do SEI com o Barramento de Serviços do PEN

Acesse a funcionalidade **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Hipóteses Legais]** para configurar as hipóteses legais do SEI com a relação de hipóteses pré-definidas pelo Barramento de Serviços do PEN.

Esta vinculação é necessária para que o módulo de integração possa traduzir corretamente as hipóteses legais definidas no SEI para o subconjunto de hipóteses aceitas pelos Barramento de Serviços do PEN no momento do envio ou recebimento de processos.

Este mapeamento precisa ser feito tanto para o envio de processos como para o recebimento, sendo necessário realizar a configuração através das duas funcionalidades apresentadas abaixo:

- SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Hipóteses Legais > **Envio**
- SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Hipóteses Legais > **Recebimento**


#### 2.4.1 Hipótese de Restrição Padrão
A configuração de Hipótese de Restrição Padrão define qual será o comportamento do sistema ao enviar ou receber processos/documentos que contenham restrição de acesso com hipóteses legais não mapeadas previamente pelo Administrador. Neste caso, a hipótese legal padrão configurado será aplicado automaticamente, evitando que o trâmite seja cancelado pela falta de mapeamento.

---
---

## 3. CONFIGURAÇÕES ADICIONAIS

Esta seção apresenta algumas configurações adicionais do módulo do Barramento de Serviços do PEN que não são obrigatórias para o funcionamento da integração, mas adicionam maior segurança, confiabilidade e desempenho ao módulo. Portanto, todas essas parametrizações são de cunho técnico e devem ser executados pela equipe de Tecnologia da Informação de sua instituição.


### 3.1. Instalação do Gearmand para processamento assíncrono de tarefas

O Gearman é um servidor utilizado para gerenciar o processamento paralelo de tarefas distribuidos em diferentes máquinas e processos, adicionando maior disponibilidade e rapidez no recebimentos de processos do Barramento de Serviços do PEN. Maiores informações do Gearman podem ser encontradas na página oficial do projeto em http://gearman.org/.

A utilização deste componente na infraestrutura do módulo de Integração com o Barramento de Serviços permite que a carga de trabalho possa ser distribuída em vários nós de aplicação do SEI de forma organizada e gerenciada pelo Gearman.

Para fazer a sua utilização, os seguintes passos precisam ser realizados:

#### 3.1.1. Instalar o servidor **gearmand**

Deverá ser selecionado, na atual infraestrutura do SEI, um nó de aplicação para a instalação do servidor de gerenciamento de filas de tarefas, o Gearmand. Como sugestão, o próprio nó de aplicação que atualmente é utilizado para a configuração dos agendamentos de tarefas do SEI com o uso do CRON pode ser utilizado para este propósito.

Os procedimentos detalhados de instalação do Gearman podem ser encontrados no seguinte endereço: http://gearman.org/getting-started.

Como exemplo de instalação das bibliotecas do Gearman considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
# CentoOS/RHEL/Fedora
yum install gearmand

# Debian/Ubuntu
apt-get install gearman-job-server

```

#### 3.1.2. Instalar as bibliotecas PHP de conexão ao Gearman

Instalar as bibliotecas PHP para conexão ao servidor do Gearman em todos os nós de aplicação do SEI, ou seja, em todos os nós de aplicação em que o servidor web (apache).

Maiores detalhes de como instalar as extensões PHP para uso no Gearman podem ser encontradas em http://gearman.org/download/#php

Como exemplo de instalação das bibliotecas do Gearman considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
# CentoOS/RHEL/Fedora
yum install epel-release && yum update # Caso necessário
yum install libgearman libgearman-devel php56*-pecl-gearman

# Debian/Ubuntu
apt-get install libgearman libgearman-dev
pecl install gearman

```

Para verificar se a biblioteca do Gearman foi corretamente instalada e caregada pelo PHP, execute o seguinte comando:

```bash
php -i | grep gearman

```

O resultado esperado é a informação da versão da biblioteca do Gearman que foi carregada e a indicação de que ela se encontra carregada (enabled). 

Caso a instalação tenha sido realizada com sucesso mas o PHP não reconheceu as bibliotecas do GEARMAN, é provável que extensão não foi devidamente configurada no arquivo de configuração do PHP e será necessário adicionar a linha abaixo no arquivo ´´´php.ini´´´.

´´´
extension="gearman.so"
´´´


#### 3.1.3. Configurar o módulo para acesso ao Gearman

Altere os parâmetros da seção ```Gearman``` no arquivo de configuração específico do módulo em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php```, conforme apresentado abaixo:

```php
"Gearman" => array(
    "Servidor" => "",
    "Porta" => "", 
),
```
Onde: 

* **Servidor**  
*IP ou Hostname do servidor do Gearman*

* **Porta**  
*Porta utilizada para conexão ao servidor do Gearman. Valor padrão 4730*


#### 3.1.4. Iniciar o serviço **Gearman**

Iniciar o serviço do Gearmand instalado nos passos anteriores.  
Exemplo de inicialização considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
$ service gearmand start
```

#### 3.1.5. Verificação da instalação e configuração do Gearman

O script de verificação da instalação do **mod-sei-pen** 2.0.0 também pode ser utilizado para validar se os passos da instalação foram realizadas com sucesso e se o módulo está conextando corretamente ao servidor do Gearmand.

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

O resultado esperado para uma correta instalação e configuração do módulo é este apresentado abaixo:

```
INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN**:
    - Arquivos do módulo posicionados corretamente
    - Módulo corretamente ativado no arquivo de configuracao do sistema
    - Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php
    - Verificada a compatibilidade do **mod-sei-pen** com a atual versão do SEI
    - Certificado digital localizado e corretamente configurado
    - Base de dados do SEI corretamente atualizada com a versão atual do **mod-sei-pen**
    - Conexão com o Barramento de Serviços do PEN realizada com sucesso
    - Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso
    - Conexão com o servidor de processamento de tarefas Gearman realizada com sucesso

** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN** FINALIZADA COM SECESSO **
```

### 3.2. Opcional: Instalação do Supervisor para monitoramento

O supervisor é o componente responsável pelo gerenciamento dos processos de monitoramento e processamentos dos eventos gerados pelo Barramento de Serviços do PEN. Sua principal função é garantir que nenhum dos processos PHP envolvidos com o envio e recebimento de processos ficaram indisponíveis em caso de falha ou indisponibilidade do sistema, o que poderia acarretar atrasos no recebimento de documentos.

A partir da versão **mod-sei-pen** 2.0.0, este componente passou a ser opcional pois o próprio agendamento de tarefas do SEI ficará encarregado de obter os processos pendentes do Barramento e processar seu recebimento. Tarefas executadas a cada 2 minutos, seguindo a configuração padrão do agendamento **PENAgendamentoRN::processarTarefasPEN**.

Caso seja necessário maior agilidade no recebimento dos processos e aumentar a garantia de disponibilidade dos serviços de conexão ao Barramento do PEN, o Supervisor pode ser utilizado.

**Atenção:** Deverá ser utilizado o Supervisor a partir da versão 4.0. 

#### 3.2.1. Instalar o **supervisord** nos nós de aplicação do SEI

Para maiores orientações sobre como realizar a instalação em diferentes distribuições do Linux, acessar a documentação oficial em http://supervisord.org/installing.html

Como exemplo de instalação do Supervisor considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
# Acessar http://supervisord.org/installing.html para maiores detalhes

# Obrigatório uso do Python 3 e Supervisor 4.x
pip install supervisor>=4
mkdir -p /etc/supervisor/ /var/log/supervisor/
echo_supervisord_conf > /etc/supervisor/supervisord.conf
```

#### 3.2.2. Configuração da inicialização automática do Supervisord 
A inicialização automática do Supervisor não é configurada durante sua instalação, portanto, é necessário configurar um script de inicialização para o serviço. No repositório oficial do projeto existe uma exemplos de scripts de inicialização do Supervisor específico para cada distribuição Linux. Estes script podem ser encontrados no endereço: https://github.com/Supervisor/initscripts

--- 

#### 3.2.3. Configuração dos serviços de recebimento de processos no **supervisor** 

Neste passo será configurado o serviço de monitoramento de pendências de trâmite para escultar as mensagens do Barramento de Serviços do PEN e processar o recebimento de processos.

Para configurar este serviço, será necessário incluir as configurações do módulo ao arquivo de configuração do Supervisor, localizado em /etc/supervisor/supervisord.conf.

O arquivo de configuração do supervisor para utilização no SEI **supervisor.exemplo.ini** vem com o sufixo **exemplo** justamente para não substituir o arquivo principal contendo as configurações vigentes do módulo.

Caso não exista o arquivo principal de configurações do módulo criado em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/supervisor.ini**, renomeie o arquivo de exemplo.

```
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/
mv supervisor.exemplo.ini supervisor.ini
```

Na chave de configuração *files* da seção *[include]*, informe o caminho completo para o arquivo **supervisor.ini**, configurações do supervisor preparadas exclusivamente para o módulo. Este arquivo fica localizado na pasta de configurações do SEI (```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/supervisor.ini```)

Exemplo: 

**Atenção:** Substituir o termo ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>``` pelo diretório raiz em que o SEI e SIP foram configurados no servidor web.

```ini
[include]
files = <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/supervisor.ini
```

As configurações contidas no arquivo **supervisor.ini** devem ser revisadas para certificar se não existem divergências em relação ao ambiente em que o módulo está sendo instalado, principalmente em relação as chaves de configurações *[user]* e *[directory]*, que deverão ser configurados respectivamente com o usuário do serviço web/http (verifique no seu servidor qual é o usuario. Ex.: apache) e com o diretório em que o sei está instalado.


#### 3.2.4. Iniciar o serviço **Supervisor*

Iniciar o serviço do Supervisor instalado nos passos anteriores.  
Exemplo de inicialização considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
$ supervisord
```

Executar o comando **supervisorctl** e verificar se os processos _sei_monitorar_pendencias_ e _sei_processar_pendencias_ estão em execução. Exemplo de resultado esperado pelo execução do comando acima:

```bash
$ supervisorctl status

sei_monitorar_pendencias                               RUNNING   pid 1272, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_00   RUNNING   pid 1269, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_01   RUNNING   pid 1270, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_02   RUNNING   pid 1268, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_03   RUNNING   pid 1271, uptime 0:00:05
```

Caso os dois serviços mencionados anteriormente não estiverem com status _RUNNING_, significa que houve algum problema de configuração na inicialização dos serviços e o recebimento de processos ficará desativado. Para maiores informações sobre o problema, acessar os arquivos de log do supervisor localizados em _/tmp/supervisord.log_ ou na pasta _/var/log/supervisor/_.

**Atenção**: Importante colocar o serviço para ser iniciado automaticamente juntamente com o servidor. 

---

### 3.3. Opcional: Script de Monitoramento

Atualmente o script verificar-servicos.sh monitora se os serviços gearmand e supervisord estão no ar. 
Identificamos que além dos serviços estarem no ar, uma verificação adicional faz-se necessária para garantir que não haja pendências a serem processadas no barramento.

Elaboramos duas rotinas adicionais, que opcionalmente poderão ser configuradas ao ambiente para monitoramento e reboot automático da fila:

1. A rotina "verificar-pendencias-represadas.py" é uma rotina verificadora para notificar ferramentas de monitoramento (ex.: Nagios) sobre trâmites parados sem processamento há xx minutos no barramento. Maiores detalhes de seu uso podem ser lidos no comentário dentro do próprio arquivo;

2. A rotina "verificar-reboot-fila.sh" poderá ser configurada no crontab do nó onde roda o supervisor. Através da rotina 1 acima, vai rebootar a fila e evitar que sejam necessários reboots manuais no supervisor caso haja alguma eventual paralisação no processamento;

Essas rotinas podem ter que sofrer alguns ajustes em seus comandos a depender do SO utilizado.
A sugestão acima foi testada em CentOs7.3. A rotina em python foi testada em python 2 e python 3.
Dúvidas com as rotinas favor abrir chamado em [https://portaldeservicos.economia.gov.br](https://portaldeservicos.economia.gov.br). De posse do número do chamado pode ligar para 61 2020-8711 (falar com Marcelo)

---
---


## 4. PROBLEMAS CONHECIDOS

### 4.1. Problema com validação de certificados HTTPS

Caso o ambiente do ConectaGov PEN utilizado nesta configuração esteja utilizando HTTPS com certificado digital não reconhecido  pela ICP-Brasil, será necessário configurar a cadeia não reconhecida como confiável nos servidores de aplicação do SEI. Com isto, os seguintes comandos precisam ser executados em cada nós de aplicação do SEI, incluindo aquele responsável pelo tratamento das tarefas agendadas.

**Caso o sistema seja Debian ou Ubuntu:**

    # Copie o certificado da cadeia de CA (Autoridade Certificadora) que assinou o certificado # fornecido PEN, para o diretório /usr/local/share/ca-certificates:

        cp <CADEIA-CERTIFICADO-CA> /usr/local/share/ca-certificates

    #Efetue a atualização da lista de certificados confiáveis do sistema operacional

        sudo update-ca-certificates

**Caso o sistema seja CentOS 6:**

    # Instalar o pacote ca-certificates package:
        
        yum install ca-certificates

    # Habilitar o recurso de configuração dinâmica de CA

        update-ca-trust force-enable

    # Copiar o arquivo da CA (Autoridade Certificadora) que assinou o certificado fornecido 
    # no passo 7, para a pasta /etc/pki/ca-trust/source/anchors/: 

        cp <CADEIA-CERTIFICADO-CA> /etc/pki/ca-trust/source/anchors/

    # Usar o comando
        
        update-ca-trust extract

### 4.2. Trâmites não realizados ou recibos não obtidos

Verificar se os serviços *gearman* e *supervisord* estão em execução, conforme orientado no manual de instalação, itens 3 e 4.

### 4.3. Erro na inicialização do Gearmand "(Address family not supported by protocol) -> libgearman-server/gearmand.cc:442"

Este problema ocorre quando o servidor não possui suporte ao protocolo IPv6. Por padrão, o Gearman inicia sua comunicação utilizando a porta 4730 e utilizando IPv4 e IPv6. Caso um destes não estejam disponíveis, o serviço não será ativado e um log de erro será gerado com a mensagem "(Address family not supported by protocol) -> libgearman-server/gearmand.cc:442".

Para solucionar o problema, duas ações podem ser feitas:
- habilitar o suporte à IPv6 no servidor de aplicação onde o Gearman foi instalado
- configurar o serviço gearmand para utilizar somente IPv4. Para fazer esta configuração, será necessário editar o arquivo de inicialização do Gearmand, normalmente localizado em /etc/sysconfig/gearmand, e adicionar o seguinte parâmetro de inicialização: OPTIONS="--listen=127.0.0.1"


### 4.4. Conversão do Certificado Digital do formato .P12 para o formato .PEM

O formato do certificado digital utilizado pela integração do do SEI com o Processo Eletrônico Nacional é o PEM, com isto, os certificados em outros formatos devem ser convertidos para este formato.
Para converter o arquivo p12 para PEM, utilize o seguinte comando.
ps: Podem existir pequenas variações de acordo com a distribuição do Linux utilizada

openssl pkcs12 --nodes -in <LOCALIZAÇÃO_CERTIFICADO_P12> -out <LOCALIZAÇÃO_CERTIFICADO_PEM>

### 4.5. Como obter o Fingerprint e Common Name do certificado digital

Para realizar a configuração do sistema de processo eletrônico na infraestrutura de serviços do Processo Eletrônico Nacional, é necessário a utilização de um Certificado Digital válido que deverá ter suas informações de Fingerprint e Common Name repassadas para a equipe do Processo Eletrônico Nacional fazer as devidas configurações do órgão. 
Com isto, para obter tais informações do certificado digital, os seguintes comandos podem ser uitlizados, lembrando que podem existir pequenas variações de acordo com a distribuição do Linux utilizada:

Fingerprint:
        
        openssl x509 -noout -fingerprint -sha1 -inform pem -in <LOCALIZAÇÃO COMPLETA DO CERTIFICADO DIGITAL>


Common Name:

        openssl x509 -noout -subject -sha1 -inform pem -in <LOCALIZAÇÃO COMPLETA DO CERTIFICADO DIGITAL>



### 4.6. Configurar as permissões de segurança para os perfis e unidades que poderão realizar o trâmite externo de processos. 

Por padrão, as funcionalidades básicas criadas pelo módulo são atribuídas automaticamente ao perfil Básico, simplificando os procedimentos de configuração do módulo.

Caso seja necessário atribuir as permissões de trâmite externo a um grupo restrito de usuários, sugerimos que os recursos descritos abaixo sejam removidos do perfil de usuários Básico e que seja criado um novo perfil que receberá estas mesmas permissões.

Para criação do novo perfil e atribuição dos devidos recursos, acesse [**SIP > Perfil > Novo**]

Exemplo: ***Perfil: Envio Externo***
    
Recursos:
~~~~    
    * pen_procedimento_expedido_listar  
    * pen_procedimento_expedir
~~~~

### 4.6. Ajuste para órgãos que alterem a URL de acesso do SEI

Caso o órgão tenha alterado a URL de acesso, seja ajuste de protocolo http para https ou de hostname, será necessário adicionar o array abaixo no ConfiguracaoModPEN.php dentro da chave "PEN":

```php
'PEN' => array(
    'WebService' => ...,
    'WebServicePendencias' => ...,
    'LocalizacaoCertificado' => ...,
    'SenhaCertificado' => ...,
    'NumeroTentativasErro' => ...,
    'ControleURL' => array(
                    "atual"=>"https://[servidor_php_atual]",
                    "antigos"=>array(
                                     "http://[servidor_php]",
                                     "http://[servidor_php2]",
                                     )
    )
```

    
---
---

## 5. SUPORTE

Em caso de dúvidas ou problemas durante o procedimento de atualização, favor entrar em conta pelos canais de atendimento disponibilizados na Central de Atendimento do Processo Eletrônico Nacional, que conta com uma equipe para avaliar e responder esta questão de forma mais rápida possível.

Para mais informações, contate a equipe responsável por meio dos seguintes canais:
- [Portal de Atendimento (PEN): Canal de Atendimento](https://portaldeservicos.economia.gov.br) - Módulo do Barramento
- Telefone: 0800 978 9005


# Módulo de Integração do Processo Eletrônico Nacional - PEN

O módulo **PEN** é o responsável por integrar o Sistema Eletrônico de Informações - SEI à plataforma de interoperabilidade do Processo Eletrônico Nacional - PEN. Este projeto tem como objetivo interligar todos os sistema de processo eletrônico do Poder Executivo Federal a fim de proporcionar a troca de documentos oficiais de forma rápida, simplificada e segura.

A utilização deste módulo adicionará novas funcionalidades ao SEI, permitindo, entre outros:
 - Enviar e receber processos administrativos de outras instituições
 - Acompanhar a relação de processos em trâmite externo
 
Para maiores informações sobre o Barramento de Serviços e o PEN, acesse http://processoeletronico.gov.br/index.php/assuntos/produtos/barramento.

Este documento está estruturado nas seguintes seções:

1. **[Instalação](#instalação)**:
Procedimentos de instalação do módulo nos servidores de aplicação e atualização do banco de dados.

2. **[Atualização](#atualização)**:
Procedimentos para realizar a atualização de uma nova versão do módulo

3. **[Script de Monitoramento](#script-de-monitoramento)**:
Procedimentos para configuração de um monitor para a fila de pendências do barramento e seu reboot automático quando necessário

4. **[Suporte](#suporte)**:
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

5. **[Problemas Conhecidos](#problemas-conhecidos)**:
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

---

## 1. Instalação

### Pré-requisitos
 - **SEI versão 3.1.x ou superior instalada**;
 - Usuário de acesso ao banco de dados do SEI e SIP com permissões para criar novas estruturas no banco de dados
 - Certificado Digital de autenticação de sistema no Barramento do PEN emitido pela equipe do Processo Eletrônico Nacional após aprovação do Comitê Gestor De Protocolo.
  
Para iniciar os procedimentos de configuração, será necessário registrar no **Barramento de Serviços do PEN** às unidades administrativas que poderão realizar o envio e recebimento de processos externo no SEI. Este procedimento precisa ser realizado pelo **Gestor de Protocolo**, previamente habilitado no portal do **Barramento de Serviços**. Lembrando que os testes devem ser feitos primeiro em ambiente de homologação para, posteriormente, a utilização em produção ser liberada. Para solicitação de acesso aos ambientes, acesse os seguintes endereços:

 - **Homologação:** [https://homolog.gestaopen.processoeletronico.gov.br/solicitarCadastroComite](https://homolog.gestaopen.processoeletronico.gov.br/solicitarCadastroComite "HOMOLOGAÇÃO: Portal de Administração Barramento de Serviços do PEN - Cadastro de Comitê")
 - **Produção:** [http://conectagov.processoeletronico.gov.br/solicitarCadastroComite](https://gestaopen.processoeletronico.gov.br/solicitarCadastroComite "PRODUÇÃO: Portal de Administração Barramento de Serviços do PEN - Cadastro de Comitê")

Para maiores informações, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço http://processoeletronico.gov.br/index.php/assuntos/produtos/barramento


### Procedimentos

#### 1. Fazer backup dos bancos de dados do SEI, SIP e dos arquivos de configuração do sistema.

Todos os procedimentos de manutenção do sistema devem ser precedidos de backup completo de todo o sistema a fim de possibilitar a sua recuperação em caso de falha. A rotina de instalação descrita abaixo atualiza tanto o banco de dados, como os arquivos pré-instalados do módulo e, por isto, todas estas informações precisam ser resguardadas.

---

#### 2. Baixar o arquivo de distribuição do mod-sei-pen

Necessário realizar o _download_ do pacote de distribuição do módulo mod-sei-pen para instalação ou atualização do sistema SEI. O pacote de distribuição consiste em um arquivo zip com a denominação mod-sei-pen-VERSAO.zip e sua última versão pode ser encontrada em https://github.com/spbgovbr/mod-sei-pen/releases

---

#### 3. Descompactar o pacote de instalação e atualizar os arquivos do sistema

Após realizar a descompactação do arquivo zip de instalação, será criada uma pasta contendo a seguinte estrutura:

```
/mod-sei-pen-VERSAO 
    /sei              # Pasta com arquivos do módulo posicionados corretamente dentro da estrutura do SEI
    /sip              # Pasta com arquivos do módulo posicionados corretamente dentro da estrutura do SIP
    /INSTALL.md       # Arquivo contendo instruções de instalação e atualização do mod-sei-pen
```

Importante enfatizar que os arquivos contidos dentro dos diretórios sei e sip não substituem nenhum código original do sistema. Eles apenas posiciona os arquivos do módulos nas pastas corretas do sistema para scripts, configurações e pasta de módulos, todos posicionados dentro de um diretório específico denominado mod-pen para deixar claro quais scripts fazem parte do mod-sei-pen.

Os diretórios sei e sip descompactados acima devem ser mesclados com os diretórios originais do SEI e SIP através de uma cópia simples dos arquivos.

Observação: O termo curinga VERSAO deve ser substituído nas instruções abaixo pelo número de versão do módulo que está sendo instalado

```
cp /tmp/mod-sei-pen-VERSAO.zip <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
unzip mod-sei-pen-VERSAO.zip
```
---

#### 4.  Habilitar módulo mod-sei-pen no arquivo de configuração do SEI

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

#### 5. Configurar os parâmetros do Módulo de Integração PEN

A instalação da nova versão do mod-sei-pen cria um arquivo de configuração específico para o módulo dentro da pasta de configuração do SEI (**<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen**). 

O arquivo de configuração padrão criado **ConfiguracaoModPEN.exemplo.php** vem com o sufixo **exemplo** justamente para não substituir o arquivo principal contendo as configurações das versões anteriores.

Caso não exista o arquivo principal de configurações do módulo criado em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php**, renomeie o arquivo de exemplo para iniciar a parametrização da integração.

```
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/
mv ConfiguracaoModPEN.exemplo.php ConfiguracaoModPEN.php
```

Altere o arquivo de configuração específico do módulo em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php** e defina as configurações do módulo, conforme apresentado abaixo:

* **WebService**  
Endereço do Web Service principal de integração com o Barramento de Serviços do PEN. Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.api.processoeletronico.gov.br/interoperabilidade/soap/v2/*
    * Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/*


* **LocalizaçãoCertificado**  
Localização completa do certificado digital utilizado para autenticação nos serviços do Barramento de Serviços do PEN. Os certificados digitais são disponibilizados pela equipe do Processo Eletrônico Nacional mediante aprovação do credenciamento da instituição. Verifique a seção [pré-requisitos](#pré-requisitos) para maiores informações.  
Necessário que o arquivo de certificado esteja localizado dentro da pasta de configurações do módulo:
```
Exemplo: <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/certificado.pem
```

* **SenhaCertificado**  
Senha do certificado digital necessário para a aplicação descriptografar e acessar a sua chave privada.


* **NumeroTentativasErro** _(opcional)_
*Quantidade de tentativas de requisção dos serviços do Barramento PEN antes que um erro possa ser lançado pela aplicação*
*Necessário para aumentar a resiliência da integração em contextos de instabilidade de rede. *
*Valor padrão: 3*

* **Gearman** _(opcional e altamente desejável)_  
Localização do servidor Gearman de gerenciamento de fila de processamento de tarefas do Barramento PEN.  
As mensagens recebidas do Barramento são organizadas em filas de tarefas e distribuídas entre os nós da aplicação para processamento coordenado. Caso este parâmetro não seja configurado ou o servidor do Gearman esteja indisponível, o processamento será feito diretamente pelo sistema na periodicidade definida no agendamento da tarefa _PENAgendamentoRN::processarTarefasPEN_.  
Veja [Processamento paralelo de processos com Gearman]((#processamento-paralelo-de-multiplos-processos-com-Gearman)) para maiores informações.

    * **Servidor**  
    *IP ou Hostname do servidor Gearman instalado*

    * **Porta**  
    *Porta utilizada para conexão ao servidor do Gearman. Valor padrão 4730*


* **WebServicePendencias** _(opcional)_  
Endereço do Web Service de monitoramente de pendências de trâmite no Barramento de Serviços do PEN
Configuração necessária somente quando o módulo é configurado para utilização conjunta com o Supervisor para monitorar ativamente todos os eventos de envio e recebimentos de processos enviados pelo Barramento de Serviços do PEN. Para maiores informações sobre como utilzar este recurso. Veja a seção [Conexão persistente com uso do Supervisor](#Conexão-persistente-com-uso-do-Supervisor) para maiores informações. \
Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.pendencias.processoeletronico.gov.br/
    * Produção: https://pendencias.conectagov.processoeletronico.gov.br/  



---

#### 5. Atualizar a base de dados do SEI com as tabelas do mod-sei-pen

Nesta etapa é instalado/atualizado as tabelas de banco de dados vinculadas do mod-sei-pen. Todas estas tabelas possuem o prefixo **md_pen_** para organização e fácil localização no banco de dados.

Executar o script **sei\_atualizar\_versao\_modulo_pen.php** para atualizar o banco de dados do SIP para o funcionamento do módulo:

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/sei_atualizar_versao_modulo_pen.php
```

---

#### 6. Atualizar a base de dados do SIP com as tabelas do mod-sei-pen

A atualização realizada no SIP não cria nenhuma tabela específica para o módulo, apenas é aplicada a criarção os recursos, permissões e menus de sistema utilizados pelo mod-sei-pen. Todos os novos recursos criados possuem o prefixo **pen_** para fácil localização pelas funcionalidades de gerenciamento de recursos do SIP.

Executar o script **sip\_atualizar\_versao\_modulo_pen.php** para atualizar o banco de dados do SIP para o funcionamento do módulo:

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/sip_atualizar_versao_modulo_pen.php
```

---

#### 7. Configuração de unidade administrativa virtual para gerenciamento de envio e recebimento de processos pelo módulo.

Esta configuração é necessária para o SEI realizar as devidas regras de registro de históricos de trâmites externos e bloqueio de edição metadados de processos/documentos. Tal unidade será utilizada internamente pelo módulo e não deverá ter acesso liberado para nenhum usuário do sistema.

#####    11.1. Acessar o SIP e criar uma nova unidade administrativa com as seguintes configurações:
    
    Sigla: EXTERNO
    Nome: Unidade Externa

#####    11.2. Configurar a nova unidade na hierarquia do SEI, através da funcionalidade **[SIP > Hierarquias > Montar]**

A unidade EXTERNO sugerida anteriormente será utilizada apenas para o correto registros dos históricos de andamento dos processos e não deverá receber nenhum processo ou acesso de usuários. Portanto, sua localização na hierarquia de unidades do SEI precisa ser feita para que o sistema sincronize tais informações do SIP para o SEI, mas seu real posicionamento na hierarquia é irrelevante neste cenário. Desta forma, unidade pode ser adicionada em qualquer ponto da hierarquia, inclusive na raiz.

---

#### 12. Configuração de tipo de processo a ser aplicado aos processos recebidos de instituições externas.

Como o processo de recebimento de novos processos será feito de forma automática pelo módulo de integração, o sistema precisa atribuir um Tipo de Processo padrão para o novo procedimento recebido. Importante lembrar que a criação de um novo tipo de processo não é obrigatório, sendo possível utilizar outro pré-existente. 

Caso a opção for pela criação de um novo tipo de processo específico, segue abaixo sugestão para configuração:

    Nome: Processo Recebido Externamente (a classificar) 
    Descrição: Processos recebidos de outras instituições 
    // O assunto deve ser definido juntamente com a área de documentação
    Sugestão de Assuntos: a classificar
    Níveis de Acesso Permitidos: Restrito e Público 
    Nível de Acesso Sugerido: Público 
    Processo único no órgão por usuário interessado: Não
    Interno do Sistema: Sim

---

#### 13. Configurar os parâmetros do Módulo de Integração Pen
Acesse a funcionalidade **[SEI > Administração > Processo Eletrônico Nacional > Parâmetros de Configuração]** para configurar os parâmetros de funcionamento do módulo:  

* **Repositório de Estruturas:**   
*ID do repositório de origem do órgão na estrutura organizacional. Este identificador é enviado para a instituição junto com o pacote de integração.*  
    - *Poder Executivo Federal - Valor 1 (Código de identificação da estrutura organizacional do Poder Executivo Federal)*  

* **Tipo de Processo Externo:**  
*Id do tipo de documento externo. *  
    - *Configurar com o ID do Tipo de Processo Externo configurado no passo 12*  

* **Unidade Geradora de Processo e Documento Recebido:**  
*Id da unidade de origem que serão atribuídos os documentos recebidos de um outro órgão.*   
    - *Configurar com o ID da Unidade criada no passo 11*



#### 14. Configurar as unidades do SEI que poderão realizar o envio e recebimento de trâmites externos

Os ID's de unidades são gerenciados pela própria instituição no portal do Processo Eletrônico Nacional ( http://conectagov.processoeletronico.gov.br). 
No credenciamento da instituição, estes valores serão passados pela unidade de TI  do MPDG.

Acesse o menu **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades]** e vincule as unidades administrativas com seus respectivos identificadores registrados no portal do Processo Eletrônico Nacional.

---
---

#### 15. Instalar o **gearmand** no servidor responsável por tratar o agendamento de tarefas do sistema

O gearman é o componente responsável pelo gerenciamento das filas de tarefas e eventos gerados de forma assíncrona pela infraestrutura de integração do barramento do PEN. 

Os procedimento de instalação do Gearman podem ser encontrados no seguinte endereço: http://gearman.org/getting-started.

**Importante:** É imprescindível que os dois sejam instalados **SOMENTE** no nó de aplicação em que está configurado o CRON de agendamento do SEI.

Como exemplo de instalação das bibliotecas do Gearman considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
yum install epel-release && yum update
yum install gearmand libgearman libgearman-devel php56*-pecl-gearman

```

#### 16. Instalar o **supervisord** no servidor responsável por tratar o agendamento de tarefas do sistema

O supervisor é o componente responsável pelo gerenciamento dos processos de monitoramento e processamentos dos eventos gerados pelas infraestrutura de integração do barramento de serviços do PEN. Sua principal função é garantir que nenhum dos processos envolvidos com o envio e recebimento de processos ficaram indisponíveis, o que poderia acarretar atrasos no recebimento de documentos.

**Importante:** É imprescindível que os dois sejam instalados **SOMENTE** no nó de aplicação em que está configurado o CRON de agendamento do SEI.

**Importante:** Deverá ser utilizado o Supervisor a partir da versão 4.0. 

Para maiores orientações sobre como realizar a instalação em diferentes distribuições do Linux, acessar a documentação oficial em http://supervisord.org/installing.html

Como exemplo de instalação do Supervisor considerando uma distribuição CENTOS do Linux, execute os comandos abaixo:

```bash
yum install python36
python3 -m ensurepip
python3 -m pip install supervisor==4.*
mkdir -p /etc/supervisor/ /var/log/supervisor/
echo_supervisord_conf > /etc/supervisor/supervisord.conf
```



##### Configuração da inicialização automática do Supervisord no Linux
A inicialização automática do Supervisor não é configurada durante sua instalação, portanto, é necessário configurar um script de inicialização para o serviço. No repositório oficial do projeto existe uma exemplos de scripts de inicialização do Supervisor específico para cada distribuição Linux. Estes script podem ser encontrados no endereço: https://github.com/Supervisor/initscripts

--- 

#### 16. Configuração dos serviços de recebimento de processos no **supervisor** 

Neste passo será configurado o serviço de monitoramento de pendências de trâmite para escultar as mensagens do Barramento de Serviços do PEN e processar o recebimento de processos.

O módulo utiliza a ferramenta **Supervisord** para manter a resiliência dos serviços, evitando quedas ou a não reconexão automática, caso a próprio infraestrutura do Barramento fique indisponível.

Para configurar este serviço, será necessário incluir as configurações do módulo ao arquivo de configuração do Supervisor, localizado em /etc/supervisor/supervisord.conf.

Na chave de configuração *files* da seção *[include]*, informe o caminho completo para o arquivo supervisord.conf.php, configurações do supervisor preparadas exclusivamente para o módulo. Este arquivo fica localizado na pasta *config* do módulo.

Exemplo: 

```ini
[include]
files = /opt/sei/web/modulos/pen/config/supervisord.conf.php
```

As configurações contidas no arquivo *config/supervisord.conf.php* devem ser revisadas para certificar se não existem divergências em relação ao ambiente em que o módulo está sendo instalado, principalmente em relação a chave de configuração *[user]*, que deverá ser configurado com o usuário do serviço web/http (verifique no seu servidor qual é o usuario. Ex.: apache)

--- 

#### 17. Iniciar serviços de monitoramento de pendências de trâmite **Gearman** e **Supervisor**

```bash
service gearmand start && supervisord
```

Executar o comando **supervisorctl** e verificar se os processos _sei_monitorar_pendencias_ e _sei_processar_pendencias_ estão em execução: 

Exemplo de resultado esperado pelo execução do comando acima:
```bash
[root@servidor_xyz /]# supervisorctl
sei_monitorar_pendencias                               RUNNING   pid 1272, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_00   RUNNING   pid 1269, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_01   RUNNING   pid 1270, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_02   RUNNING   pid 1268, uptime 0:00:05
sei_processar_pendencias:sei_processar_pendencias_03   RUNNING   pid 1271, uptime 0:00:05
```

Caso os dois serviços mencionados anteriormente não estiverem com status _RUNNING_, significa que houve algum problema de configuração na inicialização dos serviços e o recebimento de processos ficará desativado. Para maiores informações sobre o problema, acessar os arquivos de log do supervisor localizados em _/tmp/supervisord.log_ ou na pasta _/var/log/supervisor/_.
**Atenção**: Importante colocar o serviço para ser iniciado automaticamente juntamente com o servidor. 

---
#### 18. Realizar o mapeamento de tipos de documentos do SEI com as especies documentais definidas no PEN, tanto de envio quanto de recebimento. 

Esta configuração deve ser feita antes de começar a utilização do módulo.
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Recebimento** >> Cadastrar

**Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

---
#### 19. Realizar o mapeamento das hipóteses legais do SEI com as definidas no PEN para permitir o trâmite externo de processos e documentos restritos.

**Atenção**: Antes de iniciar esta configuração, será necessário executar manualmente o agendamento **PENAgendamentoRN::atualizarHipotesesLegais** em [**SEI > Infra > Agendamentos**]. Esta tarefa é necessária para atualizar o SEI com a última versão da tabela de hipóteses legais do PEN.

Este mapeamento deve ser feito antes de começar a utilização do módulo e está disponível em 
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Recebimento** >> Cadastrar

**Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

---
#### 20. O protocolo de comunicação implementado pelo PEN realiza a geração e assinatura digital de recibos de entrega e conclusão dos trâmites de processo. Para a correta geração dos recibos pelo módulo, é indispensável que todos os nós da aplicação estejam configurados com o serviço de sincronização de relógios oficial NTP.br.    
Este link pode ajudar a configurar conforme o SO utilizado: http://ntp.br/guia-linux-comum.php

---

## Script de Monitoramento

Atualmente o script verificar-servicos.sh monitora se os serviços gearmand e supervisord estão no ar. 
Identificamos que além dos serviços estarem no ar, uma verificação adicional faz-se necessária para garantir que não haja pendências a serem processadas no barramento.

Elaboramos duas rotinas adicionais, que opcionalmente poderão ser configuradas ao ambiente para monitoramento e reboot automático da fila:

1. A rotina "verificar-pendencias-represadas.py" é uma rotina verificadora para notificar ferramentas de monitoramento (ex.: Nagios) sobre trâmites parados sem processamento há xx minutos no barramento. Maiores detalhes de seu uso podem ser lidos no comentário dentro do próprio arquivo;

2. A rotina "verificar-reboot-fila.sh" poderá ser configurada no crontab do nó onde roda o supervisor. Através da rotina 1 acima, vai rebootar a fila e evitar que sejam necessários reboots manuais no supervisor caso haja alguma eventual paralisação no processamento;

Essas rotinas podem ter que sofrer alguns ajustes em seus comandos a depender do SO utilizado.
A sugestão acima foi testada em CentOs7.3. A rotina em python foi testada em python 2 e python 3.
Dúvidas com as rotinas favor abrir chamado em [http://processoeletronico.gov.br/index.php/conteudo/suporte](http://processoeletronico.gov.br/index.php/conteudo/suporte). De posse do número do chamado pode ligar para 61 2020-8711 (falar com Marcelo)


---

## Atualização

Para realizar a atualização do módulo, realize os seguintes procedimentos:

#### 1. Fazer backup dos banco de dados do SEI e SIP e dos arquivos de *configuração do sistema

---
#### 2. Baixar a última versão do módulo disponível em https://softwarepublico.gov.br/gitlab/sei/mod-sei-pen/tags

---
#### 3. Atualizar software Supervisord para a versão 4.X

Os procedimentos de atualização dependente do OS, mas a versão 4 está disponível via gerenciador de pacotes pip do python 3. Para maiores orientações sobre como realizar a instalação em diferentes distribuições do Linux, acessar a documentação oficial em http://supervisord.org/installing.html.

Versão 2.X não deve ser mais utilizada.

---
#### 4. Atualizar as configurações dos serviços de monitoramento do /etc/supervisord.conf conforme definido no passo 03 do Manual de Instalação do mod-sei-pen, seção "Configuração dos serviços de recebimento de processos no supervisor". https://softwarepublico.gov.br/gitlab/sei/mod-sei-pen/blob/master/README.md.

---
#### 5. Importante renomear a pasta do módulo "mod-sei-pen" para somente "pen" por questões de padronização de nomenclatura

---
#### 6. Mover o arquivo de instalação do módulo no SEI sei_atualizar_versao_modulo_pen.php para a pasta sei/scripts
Lembre-se de mover, e não copiar, por questões de segurança e padronização;

---
#### 7. Mover o arquivo de instalação do módulo no SIP sip_atualizar_versao_modulo_pen.php para a pasta sip/scripts
Lembre-se de mover, e não copiar, por questões de segurança e padronização;

---
#### 8. Executar o script sip_atualizar_versao_modulo_pen.php para atualizar o banco de dados do SIP para o funcionamento do módulo
```bash
php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sip/scripts/sip_atualizar_versao_modulo_pen.php
```

---
#### 9. Executar o script sei_atualizar_versao_modulo_pen.php para inserção de dados no banco do SEI referente ao módulo
```bash
php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/scripts/sei_atualizar_versao_modulo_pen.php
```

---
#### 10. Configurar a tarefa de reinicialização de serviços caso se identifique possíveis indisponibilidades. (Manual de Instalação do módulo) 
Os procedimento descritos abaixo deverão ser executados no mesmo servidor em que está instalado o supervisor e o gearman (passo 3 do Manual de Instalação do módulo).
​
Mova o script verificar-servicos.sh, localizado na raiz do diretório do módulo, para a pasta de sei/bin do SEI.

```bash
cp [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/web/modulos/pen/verificar-servicos.sh /opt/sei/bin/
```

Configure este script no serviço de agendamento CRON com uma periodicidade sugerida de 10 minutos, tempo este utilizado para o devido monitoramento e tentativa de reativação dos serviços.

```bash
crontab -e 

*/10 * * * * [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/bin/verificar-servicos.sh
```

---
#### 11. Reiniciar serviços de monitoramento de pendências de trâmite Gearman e Supervisor:
```bash
service gearmand restart && service supervisord restart
```

---
---

## Suporte

Para maiores informações e suporte ao PEN, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço https://portaldeservicos.planejamento.gov.br

## Problemas Conhecidos

### 1. Problema com validação de certificados HTTPS

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

### 2. Trâmites não realizados ou recibos não obtidos

Verificar se os serviços *gearman* e *supervisord* estão em execução, conforme orientado no manual de instalação, itens 3 e 4.

### 3. Erro na inicialização do Gearmand "(Address family not supported by protocol) -> libgearman-server/gearmand.cc:442"

Este problema ocorre quando o servidor não possui suporte ao protocolo IPv6. Por padrão, o Gearman inicia sua comunicação utilizando a porta 4730 e utilizando IPv4 e IPv6. Caso um destes não estejam disponíveis, o serviço não será ativado e um log de erro será gerado com a mensagem "(Address family not supported by protocol) -> libgearman-server/gearmand.cc:442".

Para solucionar o problema, duas ações podem ser feitas:
- habilitar o suporte à IPv6 no servidor de aplicação onde o Gearman foi instalado
- configurar o serviço gearmand para utilizar somente IPv4. Para fazer esta configuração, será necessário editar o arquivo de inicialização do Gearmand, normalmente localizado em /etc/sysconfig/gearmand, e adicionar o seguinte parâmetro de inicialização: OPTIONS="--listen=127.0.0.1"


### 4. Conversão do Certificado Digital do formato .P12 para o formato .PEM

O formato do certificado digital utilizado pela integração do do SEI com o Processo Eletrônico Nacional é o PEM, com isto, os certificados em outros formatos devem ser convertidos para este formato.
Para converter o arquivo p12 para PEM, utilize o seguinte comando.
ps: Podem existir pequenas variações de acordo com a distribuição do Linux utilizada

openssl pkcs12 --nodes -in <LOCALIZAÇÃO_CERTIFICADO_P12> -out <LOCALIZAÇÃO_CERTIFICADO_PEM>

### 5. Como obter o Fingerprint e Common Name do certificado digital

Para realizar a configuração do sistema de processo eletrônico na infraestrutura de serviços do Processo Eletrônico Nacional, é necessário a utilização de um Certificado Digital válido que deverá ter suas informações de Fingerprint e Common Name repassadas para a equipe do Processo Eletrônico Nacional fazer as devidas configurações do órgão. 
Com isto, para obter tais informações do certificado digital, os seguintes comandos podem ser uitlizados, lembrando que podem existir pequenas variações de acordo com a distribuição do Linux utilizada:

Fingerprint:
        
        openssl x509 -noout -fingerprint -sha1 -inform pem -in <LOCALIZAÇÃO COMPLETA DO CERTIFICADO DIGITAL>


Common Name:

        openssl x509 -noout -subject -sha1 -inform pem -in <LOCALIZAÇÃO COMPLETA DO CERTIFICADO DIGITAL>



### 6. Configurar as permissões de segurança para os perfis e unidades que poderão realizar o trâmite externo de processos. 

Por padrão, as funcionalidades básicas criadas pelo módulo são atribuídas automaticamente ao perfil Básico, simplificando os procedimentos de configuração do módulo.

Caso seja necessário atribuir as permissões de trâmite externo a um grupo restrito de usuários, sugerimos que os recursos descritos abaixo sejam removidos do perfil de usuários Básico e que seja criado um novo perfil que receberá estas mesmas permissões.

Para criação do novo perfil e atribuição dos devidos recursos, acesse [**SIP > Perfil > Novo**]

Exemplo: ***Perfil: Envio Externo***
    
Recursos:
~~~~    
    * pen_procedimento_expedido_listar  
    * pen_procedimento_expedir
~~~~
    
# Módulo de Integração do Processo Eletrônico Nacional - PEN

O módulo **PEN** é o responsável por integrar o Sistema Eletrônico de Informações - SEI à plataforma de interoperabilidade do Processo Eletrônico Nacional - PEN. Este projeto tem como objetivo interligar todos os sistema de processo eletrônico do Poder Executivo Federal a fim de proporcionar a troca de documentos oficiais de forma rápida, simplificar e segura.

A utilização deste módulo adicionará novas funcionalidades ao SEI, permitindo, entre outros:
 - Enviar e receber processos administrativos de outras instituições
 - Acompanhar a relação de processos em trâmite externo
 
Para maiores informações sobre o ConectaGov e o PEN, acesse http://processoeletronico.gov.br/index.php/assuntos/produtos/barramento.

Este documento está estruturado nas seguintes seções:

1. **[Instalação](#instalação)**:
Procedimentos de instalação do módulo nos servidores de aplicação e atualização do banco de dados.

2. **[Atualização](#atualização)**:
Procedimentos para realizar a atualização de uma nova versão do módulo

4. **[Utilização](#utilização)**:
Apresentação das funcionalidades que permitem o trâmite externo de processos e o acompanhamento de seu histórico.
 
5. **[Suporte](#suporte)**:
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

## Instalação

### Pré-requisitos
 - **SEI versão 3.0.11 ou superior instalada**;
 - Supervisor versão 4.X ou superior instalada;
 - Gearman instalado;
 - Usuário de acesso ao banco de dados do SEI e SIP com permissões para criar novas estruturas no banco de dados.
  
Para iniciar os procedimentos de configuração, será necessário registrar no **ConectaGov-PEN** as unidades administrativas que poderão realizar o envio e recebimento de processos/documentos externo no SEI. Este procedimento precisa ser realizado pelo **Gestor de Protocolo** previamente habilitado no portal do **ConectaGov-PEN**. Lembrando que os testes devem ser feitos primeiro em ambiente de desenvolvimento/homologação para, posteriormente, a utilização em produção ser liberada. Para solicitação de acesso aos ambientes, acesse os seguintes endereços:

 - Desenvolvimento [http://pen-portal.trafficmanager.net/solicitarCadastroComite](http://pen-portal.trafficmanager.net/solicitarCadastroComite "DESENVOLVIMENTO: Portal de Administração ConectaGov PEN - Cadastro de Comitê")
 - Homologação [http://homolog.pen.portal.trafficmanager.net/solicitarCadastroComite](http://homolog.pen.portal.trafficmanager.net/solicitarCadastroComite "HOMOLOGAÇÃO: Portal de Administração ConectaGov PEN - Cadastro de Comitê")
 - Produção [http://conectagov.processoeletronico.gov.br/solicitarCadastroComite](http://conectagov.processoeletronico.gov.br/solicitarCadastroComite "PRODUÇÃO: Portal de Administração ConectaGov PEN - Cadastro de Comitê")

 Para maiores informações, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço https://portaldeservicos.planejamento.gov.br

### Procedimentos

#### 1. Fazer backup dos bancos de dados do SEI, SIP e dos arquivos de configuração do sistema.

---

#### 2.  Mover o diretório de arquivos do módulo "pen" para o diretório sei/web/modulos/
Importante renomear a pasta do módulo "mod-sei-pen" para somente "pen" por questões de padronização de nomenclatura.

---

#### 3.  Configurar módulo no arquivo de configuração do SEI

Editar o arquivo **sei/config/ConfiguracaoSEI.php**, tomando o cuidado de usar editor que não altere o charset ISO 5589-1 do arquivo, para adicionar a referência ao módulo PEN na chave **[Modulos]** abaixo da chave **[SEI]**:    

```php
'SEI' => array(
    'URL' => 'http://[servidor sei]/sei',
    'Producao' => true,
    'RepositorioArquivos' => '/var/sei/arquivos',
    'Modulos' => array('PENIntegracao' => 'pen'),
    ),
```

Adicionar a referência ao módulo PEN na array da chave 'Modulos' indicada acima:
```php
'Modulos' => array('PENIntegracao' => 'pen')
```
---

#### 4. Mover o arquivo de instalação do módulo no SEI **sei\_atualizar\_versao\_modulo_pen.php** para a pasta **sei/scripts**. Lembre-se de mover, e não copiar, por questões de segurança e padronização.

---

#### 5. Mover o arquivo de instalação do módulo no SIP **sip\_atualizar\_versao\_modulo\_pen.php** para a pasta **sip/scripts**. Lembre-se de mover, e não copiar, por questões de segurança e padronização.

---

#### 6. Executar o script **sip\_atualizar\_versao\_modulo_pen.php** para atualizar o banco de dados do SIP para o funcionamento do módulo:
```bash
php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sip/scripts/sip_atualizar_versao_modulo_pen.php
```

---

#### 7. Executar o script **sei_atualizar_versao_modulo_pen.php** para inserção de dados no banco do SEI referente ao módulo.
```bash
php -c /etc/php.ini [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/scripts/sei_atualizar_versao_modulo_pen.php
```

---
#### 8. Mover o arquivo do certificado digital utilizado para integração com o **PEN** para o diretório "sei/config/".
    
Para melhor organização dos arquivos dentro do diretório **sei/config**, sugerimos a criação da uma nova pasta chamada **sei/config/certificados\_mod_conectagov** para adicionar estes arquivos.
    
Os certificados digitais para conectar aos ambientes de desenvolvimento e homologação do PEN estão localizados no pacote de instalação disponibilizado pela equipe técnica do Ministério da Economia - ME e são disponibilizados no ato do credenciamento da instituição no PEN. 

Para o ambiente de produção, deverá ser utilizado um certificado digital válido gerado por uma Autoridade de Registro - AR confiável (Exemplo: ICP-Brasil, Certisign, Verisign, etc.).

Maiores informações e solicitações podem ser feitas através do e-mail processo.eletronico@planejamento.gov.br.

---
#### 9. Após a instalação do módulo, o usuário de manutenção deverá ser alterado para outro contendo apenas as permissões de leitura e escrita no banco de dados.

---
#### 10. Configurar as permissões de segurança para os perfis e unidades que poderão realizar o trâmite externo de processos. 

Por padrão, as funcionalidades básicas criadas pelo módulo não são atribuídas automaticamente à um perfil específico do sistema, evitando sua disponibilização para todos os usuários do sistema sem a prévia definição dos administradores.

Sugerimos que seja criado um novo perfil de usuário que receberá as permissões incluídas pelo módulo (pen_*). Este novo perfil deverá ser atribuído aos usuários que poderão realizar o trâmite externo de processos para outras instituições. 

Para criação do novo perfil e atribuição dos devidos recursos, acesse [**SIP > Perfil > Novo**]

Exemplo: ***Perfil: Envio Externo***
    
Recursos:
~~~~    
    * pen_procedimento_expedido_listar  
    * pen_procedimento_expedir
~~~~

Recomenda-se que os recursos acima não sejam atribuídos aos perfis básicos do sistema.
    
---

#### 11. Configuração de unidade administrativa virtual para gerenciamento de envio e recebimento de processos pelo módulo.

Esta configuração é necessária para o SEI realizar as devidas regras de registro de históricos de trâmites externos e bloqueio de edição metadados de processos/documentos. Tal unidade será utilizada internamente pelo módulo e não deverá ter acesso liberado para nenhum usuário do sistema.

#####    11.1. Acessar o SIP e criar uma nova unidade administrativa com as seguintes configurações:
    
    Sigla: EXTERNO
    Nome: Unidade Externa

#####    11.2. Configurar a nova unidade na hierarquia do SEI, através da funcionalidade **[SIP > Hierarquias > Montar]**

Sugerimos que está unidade seja configurada no mesmo nível hierárquico da unidade de teste padrão existente no SEI. Para saber qual é a unidade de testes, basta verificar o parâmetro do SEI chamado **SEI_UNIDADE_TESTE**

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

* **Endereço do Web Service:**  
*Endereço dos serviços de integração do PEN* 
    - Desenvolvimento: https://pen-api.trafficmanager.net/interoperabilidade/soap/v2/
    - Homologação: https://homolog.pen.api.trafficmanager.net/interoperabilidade/soap/v2/
    - Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/  
* **Endereço do Web Service de Pendências**:  
*Endereço dos serviços de notificação de trâmites de processos*
    - Desenvolvimento: https://pen-pendencias.trafficmanager.net/
    - Homologação: https://homolog.pen.pendencias.trafficmanager.net/
    - Produção: https://pendencias.conectagov.processoeletronico.gov.br/  
* **ID do Repositório de Estruturas:**   
*ID do repositório de origem do órgão na estrutura organizacional. Este identificador é enviado para a instituição junto com o pacote de integração.*  
    - *Valor 1 (Código de identificação da estrutura organizacional do Poder Executivo Federal)*  
* **Localização do Certificado Digital:**  
    - *Localização do certificado digital o órgão (arquivo do passo 8)*  
* **Número Máximo de Tentativas de Recebimento:**  
    - *Valor padrão: 3*  
* **Tamanho Máximo de Documentos Expedidos:**  
    - *Valor padrão: 50*  
* **Senha do Certificado Digital:**  
    - *Senha do certificado digital*  
* **Tipo de Processo Externo:**  
*Id do tipo de documento externo. *  
    - *Configurar com o ID do Tipo de Processo Externo configurado no passo 12*  
* **Unidade Geradora de Processo e Documento Recebido:**  
*Id da unidade de origem que serão atribuídos os documentos recebidos de um outro órgão.*   
    - *Configurar com o ID da Unidade criada no passo 11*

---

#### 14. Configurar as unidades do SEI que poderão realizar o envio e recebimento de trâmites externos

Os ID's de unidades são gerenciados pela própria instituição no portal do Processo Eletrônico Nacional ( http://conectagov.processoeletronico.gov.br). 
No credenciamento da instituição, estes valores serão passados pela unidade de TI  do MPDG.

Acesse o menu **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades]** e vincule as unidades administrativas com seus respectivos identificadores registrados no portal do Processo Eletrônico Nacional.

---

#### 15. Instalar o **gearmand** e o **supervisord** no servidor responsável por tratar o agendamento de tarefas do sistema.

Estes dois componentes são utilizados para gerenciar a fila de recebimento de novos processos de forma assíncrona pelo SEI.

**Importante:** É imprescindível que os dois sejam instalados **SOMENTE** no nó de aplicação em que está configurado o CRON de agendamento do SEI.
**Importante:** Deverá ser utilizado o Supervisor a partir da versão 4.0. Para maiores orientações sobre como realizar a instalação em diferentes distribuições do Linux, acessar a documentação oficial em http://supervisord.org/installing.html

##### Exemplo de instalação do German e Supervisor no CentOS:
Os pacotes abaixo estão presentes em todas as distribuições do Linux

```bash
# pre-requisito
yum install epel-release && yum update

# instalação do gearman e supervisord               
yum install gearmand libgearman libgearman-devel php56*-pecl-gearman

# instalação do supervisor
yum install python3
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
;files = relative/directory/*.ini
files = /opt/sei/web/modulos/pen/config/supervisord.conf.php
```

As configurações contidas no arquivo *config/supervisord.conf.php* devem ser revisadas para certificar se não existem divergências em relação ao ambiente em que o módulo está sendo instalado, principalmente em relação a chave de configuração *[user]*, que deverá ser configurado com o usuário do serviço web/http (verifique no seu servidor qual é o usuario. Ex.: apache)

---

#### 17. Configurar a tarefa de reinicialização de serviços caso se identifique possíveis indisponibilidades.

Esta configuração é recomendada como contingência para garantir que os serviços de integração não serão desativados em caso de indisponibilidade momentânea da infraestrutura do PEN.

Os procedimento descritos abaixo deverão ser executados no mesmo servidor em que está instalado o **supervisor** e o **gearman** (passo 3). 

Mova o script **verificar-servicos.sh**, localizado na raiz do diretório do módulo, para a pasta de **sei/bin** do SEI:

```bash
cp [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/web/modulos/pen/verificar-servicos.sh /opt/sei/bin/
```

Configure este script no serviço de agendamento CRON com uma periodicidade sugerida de 10 minutos, tempo este utilizado para o devido monitoramento e tentativa de reativação dos serviços.

```bash
crontab -e 

*/10 * * * * [DIRETORIO_RAIZ_INSTALAÇÃO]/sei/bin/verificar-servicos.sh
```

--- 

#### 18. Iniciar serviços de monitoramento de pendências de trâmite **Gearman** e **Supervisor**

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
#### 19. Realizar o mapeamento de tipos de documentos do SEI com as especies documentais definidas no PEN, tanto de envio quanto de recebimento. 

Esta configuração deve ser feita antes de começar a utilização do módulo.
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Tipos de Documentos >> **Recebimento** >> Cadastrar

**Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

---
#### 20. Realizar o mapeamento das hipóteses legais do SEI com as definidas no PEN para permitir o trâmite externo de processos e documentos restritos.

**Atenção**: Antes de iniciar esta configuração, será necessário executar manualmente o agendamento **PENAgendamentoRN::atualizarHipotesesLegais** em [**SEI > Infra > Agendamentos**]. Esta tarefa é necessária para atualizar o SEI com a última versão da tabela de hipóteses legais do PEN.

Este mapeamento deve ser feito antes de começar a utilização do módulo e está disponível em 
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Envio** >> Cadastrar
    - SEI >> Administração >> Processo Eletrônico Nacional >> Mapeamento de Hipóteses Legais >> **Recebimento** >> Cadastrar

**Observação**: Os tipos de documentos a serem mapeados deverão estar configurados no SEI como Externo ou Interno/Externo 

---
#### 21. O protocolo de comunicação implementado pelo PEN realiza a geração e assinatura digital de recibos de entrega e conclusão dos trâmites de processo. Para a correta geração dos recibos pelo módulo, é indispensável que todos os nós da aplicação estejam configurados com o serviço de sincronização de relógios oficial NTP.br.    
Este link pode ajudar a configurar conforme o SO utilizado: http://ntp.br/guia-linux-comum.php

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


## Utilização

Esta seção tem por objetivo demonstrar as funcionalidades que são disponibilizadas pelo módulo de trâmite do PEN e também as configurações que devem ser realizadas no próprio SEI para o correto funcionamento.

### Informações Obrigatórias para Envio Externo de Processo

O **ConectaGov-PEN** atende a diferentes sistemas de gestão eletrônica de documentos (GED) e sistemas informatizados de gestão arquivística de documentos (SIGAD). Para permitir a interoperabilidade entre estes tipos de sistemas, definiu-se um padrão de dados para intercâmbio.

O padrão de dados define atributos que são obrigatórios e opcionais. A obrigatoriedade de alguns elementos obriga que determinadas informações sejam incluídas no processo, as quais, no SEI, originalmente, são opcionais.

Ao criar o processo, para envio externo pelo PEN, são obrigatórios os campos **especificação e interessado** (deve haver pelo menos um interessado no processo)

![Tela de Iniciar Processos - Destaque para Campos Especificação e Interessados](imagens/campos_especificacao_interessado.png)

O SEI fará uma verificação das informações pendentes para envio e exibirá uma mensagem para o usuário, conforme imagem:

![Validação dos Campos Especificação e Interessados no Momento  do Envio Externo de Processo](imagens/valida_especificacao_interessado.png)

Verifica-se também se o processo possui pelo menos um documento interno assinado ou se possui algum documento externo, além de impedir o trâmite de processos que possuam documentos sem assinatura, conforme exemplificado a seguir:

![Verificação de Existência de pelo menos um Documento no Processo](imagens/doc_nao_assinado.png)

### Envio Externo de Processo

Para realizar o trâmite externo do processo, o módulo disponibiliza ao usuário (**caso o seu perfil possua o recurso pen\_procedimento\_expedir**) um ícone na tela de processos, conforme imagem abaixo: 

![Painel de Botões do Controle de Processos - Destaque para o Ícone de Envio Externo de Processo](imagens/envio_externo_processo.png)

Ao acionar o ícone de envio externo de processo, disponibiliza-se uma tela onde mostra-se o número do processo selecionado para envio externo, que é apenas informativo, a opção de escolha do repositório de estruturas do receptor (que no caso do Poder Executivo Federal será o do SIORG -  Sistema de Organização e Inovação Institucional do Governo Federal), o nome da unidade receptora na estrutura organizacional e opção de indicar se trata-se de processo com urgência.


![Tela de Envio Externo de Processo - Destaque para as Opções Disponibilizadas](imagens/tela_envio_externo.png)

O cadastro da estrutura organizacional é feito previamente no Portal de Administração do ConectaGov PEN. A administração central do portal é feita pela equipe do Ministério do Planejamento, Desenvolvimento e Gestão - MP, embora o cadastro da estrutura propriamente dita seja feito por um perfil denominado Comitê Gestor de Protocolo, informado pelo órgão ou entidade e habilitado no portal pela equipe do MP, conforme [fluxo definido](https://www.comprasgovernamentais.gov.br/images/Barramento/FluxoCredenciais.png).
Para maiores informações sobre o Comitê Gestor de Protocolo, consulte o [manual específico](https://www.comprasgovernamentais.gov.br/images/Barramento/ManualdoGestor.pdf). 

O ConectaGov PEN permite a participação de órgãos e entidades de outros poderes e esferas administrativas, sendo no Poder Executivo Federal o uso obrigatório do SIORG, conforme parágrafo único do Art. 25, do [Decreto nº 6.944, de 21 de agosto de 2009](http://www.planalto.gov.br/ccivil_03/_Ato2007-2010/2009/Decreto/D6944.htm).   

Ao selecionar o repositório de estruturas desejado, é necessário digital o nome da unidade administrativa receptora do processo. Dinamicamente, sugere-se o nome da unidade, baseado na configuração feita no Portal de Administração do PEN. **As unidades disponíveis para envio externo dependem da configuração realizada por cada Comitê Gestor de Protocolo dos órgãos e entidades.**

![Tela de Envio Externo de Processo - Destaque para o Filtro de Unidades Administrativas para Envio Externo](imagens/selecao_unidade_envio_externo.png)

Na tela de envio externo de processo, há a opção de indicar a **urgência** para o processo. As opções são automaticamente sincronizadas a partir do serviço do PEN.

![Tela de Envio Externo de Processo - Destaque para o Filtro de Unidades Administrativas para Envio Externo](imagens/urgencia_envio_externo.png)

Ao realizar o envio externo, o módulo faz uma **série de validações no processo** para que a informação seja corretamente enviada ao serviço do PEN. O andamento do progresso de validação e envio é exibido por meio de uma janela *pop-up* do navegador web. É importante permitir a abertura de *pop-ups* no navegador web, para que a operação possa ser verificada.


![Tela de Status de Envio do Processo - Barra de Progresso](imagens/em_envio_externo_processo.png)

Uma vez que o processo tenha sido recebido com sucesso pelo PEN, a seguinte mensagem é exibida. **Mas isso não significa que a unidade destinatária recebeu e aceitou o processo, pois esta também deve fazer uma série de validações,** conforme explicado na próxima seção.

![Tela de Status de Envio do Processo - Barra de Progresso - Finalizado](imagens/confirmado_envio_externo_processo.png)

### Informações Registradas nos Andamentos do Processo (Histórico)

O ConectaGov PEN atua como uma **terceiro confiável** no trâmite do processo administrativo. Em um primeiro momento, o módulo do SEI faz uma série de validações de informações constantes do processo a ser enviado. Uma vez validadas estas informações, a operação de envio é registrada no andamento do processo. **Mas isso ainda não reflete o sucesso no trâmite de fato**, pois a unidade receptora também faz uma série de validações como, por exemplo, os tamanhos de documentos que tem capacidade de receber, as espécies documentais, hipóteses legais, dentre outras. Uma vez validados, na origem, os requisitos para envio externo,** registra-se no andamento do processo a operação de envio** por meio do ConectaGov, conforme o seguinte exemplo:

![Tela de Histórico do Processo - Processo em Envio Externo](imagens/historico_origem_envio_externo.png)

Enquanto a unidade receptora não confirma o recebimento, o SEI passa a indicar que aquele processo **encontra-se em envio externo**, aguardando o recebimento. Nesse momento, o processo encontra-se bloqueado para edição, evento que possui um alerta de um círculo vermelho à direita do seu número, na tela de Controle do Processo. No estado bloqueado, as opções disponíveis são **apenas de visualização**, sem permitir operações que alteram informações do processo.

![Tela de Controle de Processos - Processo em Envio Externo](imagens/processo_em_tramitacao.png)

O SEI, ao receber o aviso do ConectaGov PEN de que a unidade receptora validou as informações e recebeu o processo, faz o registro no andamento, indicando o sucesso no trâmite, e o **processo passa a indicar que não possui mais andamentos abertos**.

![Tela de Controle de Processos - Processo sem Andamentos Abertos](imagens/processo_bloqueado_envio_externo.png)

Abaixo, mensagem registrada no histórico, indicando a confirmação do envio externo:

![Tela de Histórico do Processo - Confirmação do Envio Externo](imagens/confirmacao_envio_externo.png)

Ainda é possível reabrir o processo na unidade, quando do envio externo ocorrido com sucesso, para que se consulte as informações do processo, caso assim deseje a unidade de origem. Mesmo nesse caso, **apenas a unidade que recebeu o processo** via PEN pode realizar a instrução processual, ou seja, efetuar modificações no processo administrativo.

No caso de recebimento de processos por meio do ConectaGov PEN, o processo aparece na área de trabalho da mesma forma que fosse recebido de um trâmite interno (com fonte em vermelho). É importante frisar que, como regra, os processos serão direcionados às unidades receptoras. Caso não haja unidade receptora para determinada unidade visível no PEN, o processo é remetido diretamente à unidade destinatária visível para trâmite. A configuração das unidades visíveis para trâmite e das unidades receptoras ficarão a cargo do Comitê Gestor de cada órgão ou unidade.

A operação de recebimento de processo por meio de envio externo também é registrada na unidade destinatária, conforme nota-se na imagem:

![Tela de Histórico do Processo - Informações na Unidade/Órgão Destinatários](imagens/recebimento_destinatario_historico.png)

No exemplo acima, a unidade EXTERNO é a unidade cadastrada no passo 15 deste manual. Ou seja, a unidade EXTERNO recebeu o processo do ConectaGov PEN, realizou o download dos documentos a partir do ConectaGov e encaminhou o processo para a devida unidade, de forma automática.

A unidade destinatária pode fazer a instrução processual normalmente, inclusive fazendo a devolução do processo para a unidade originária. Neste caso, o PEN consegue reconhecer os documentos que a unidade receptora já possui, realizando, assim, o **trâmite apenas daqueles documentos necessários para a unidade de origem**. 
 
### Consulta de Recibos

O PEN disponibiliza recibos a respeito das operações realizadas. Os casos de disponibilização de recibos são o de envio para o ConectaGov PEN, disponibilizado ao remetente, e de conclusão de trâmite (disponibilizado para o remetente e o destinatário, para indicar que o destinatário recebeu com sucesso todos os documentos e processos).

Para consultar os recibos gerados, deve-se acessar o ícone correspondente, na barra de controle de processos, conforme imagem seguinte:

![Tela de Controle do Processo - Ícone de Consulta de Recibos](imagens/icone_consulta_recibos.png)

Para o remetente, disponibilizam-se os recibos de envio e de conclusão de trâmite, conforme imagens seguintes.
O recibo de envio indica que o ConectaGov PEN recebeu com sucesso os documentos e processos de forma íntegra.

![Tela de Controle do Processo - Ícone de Consulta de Recibos](imagens/recibo_confirmacao_envio.png)

O recibo de trâmite indica que o ConectaGov PEN conseguiu entregar com sucesso os documentos e processos de forma íntegra ao destinatário.

![Tela de Controle do Processo - Ícone de Consulta de Recibos](imagens/recibo_confirmacao_tramite.png)

O destinatário pode realizar a consulta ao recibo de trâmite, acessando o ícone de recibos, conforme imagem seguinte.

![Tela de Controle do Processo - Ícone de Consulta de Recibos](imagens/recibo_conclusao_tramite_destinatario.png)

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
        



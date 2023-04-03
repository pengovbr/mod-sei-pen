# Manual de Atualização do Módulo de Integração do Processo Eletrônico Nacional - PEN

O objetivo deste documento é descrever os procedimento para ATUALIZAÇÃO do Módulo de Integração com o Tramita.GOV.BR (**mod-sei-pen**) previamente instalado e configurado no Sistema Eletrônico de Informações (SEI).

**ATENÇÃO: Caso esta seja a primeira instalação do módulo no SEI, veja as instruções detalhadas de instalação no documento INSTALACAO.md presente no arquivo de distribuição do módulo (mod-sei-pen-VERSAO.zip)**

 
Para maiores informações, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço http://processoeletronico.gov.br/index.php/assuntos/produtos/barramento


Este documento está estruturado nas seguintes seções:

1. **[Atualização](#atualização)**:  
Procedimentos para realizar a atualização de uma nova versão do módulo

    **[1.1. Atualização Simples (versão 2.0.x -> versão superior 2.0.x)](#)**  
    **[1.2. Atualização Completa (versão 1.5.x para 2.0.0)](#)**  

2. **[Configuração](#configuração)**:  
Procedimentos destinados ao Administradores do SEI responsáveis pela configuração do módulo através da funcionalidades de administração do sistema.

3. **[Suporte](#suporte)**:  
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

4. **[Problemas Conhecidos](#problemas-conhecidos)**:  
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

---

## 1. ATUALIZAÇÃO

Esta seção descreve os passos obrigatórios para **ATUALIZAÇÃO** do **```mod-sei-pen```**.  
Todos os itens descritos nesta seção são destinados à equipe de tecnologia da informação, responsáveis pela execução dos procedimentos técnicos de instalação e manutenção da infraestrutura do SEI.


### Atenção: Verifique a versão atualmente instalada para aplicar os procedimentos corretos de atualização
Os procedimentos abaixo estão divididos em duas seções diferentes em que cada uma descreve os procedimentos de atualização considerando a versão atualmente instalada.  

Como houve uma quebra de compatibidade entre a versão mod-sei-pen 1.5.4 para a versão 2.0.0, a atualização desta versão possui alguns passos adicionais do que uma simples atualização entre as versões 2.0.0 para 2.x.X. 

Dito isto, siga os passos correspondes considerando:

a) Atualização mod-sei-pen 2.0.0 para 2.0.x, siga a seção 1.1. Atualização Simples (versão 2.0.x -> 2.0.x)  
b) Atualização mod-sei-pen 1.5.x para 2.0.x, siga a seção 1.2. Atualização Completa (versão 1.5.x para 2.0.0)



## 1.1. Atualização Simples (versão 2.0.x -> 2.0.x)

Procedimentos para atualização do mod-sei-pen em versões iguais ou anteriores à **2.0.0**, consistindo apenas na atualização dos arquivos do módulo e do banco de dados.



### Pré-requisitos
 - **Mod-Sei-Pen 2.0.0 ou versão superior instalada**;
 - **SEI versão 3.1.x ou versão superior instalada**;
 - **Módulo **mod-sei-pen** previamente no SEI**
 - Usuário de acesso ao banco de dados do SEI e SIP com permissões para criar novas estruturas no banco de dados


### Procedimentos:

### 1.1.1 Fazer backup dos bancos de dados do SEI, SIP e dos arquivos de configuração do sistema.

Todos os procedimentos de manutenção do sistema devem ser precedidos de backup completo de todo o sistema a fim de possibilitar a sua recuperação em caso de falha. A rotina de instalação descrita abaixo atualiza tanto o banco de dados, como os arquivos pré-instalados do módulo e, por isto, todas estas informações precisam ser resguardadas.

---

### 1.1.2. Baixar o arquivo de distribuição do mod-sei-pen

Necessário realizar o _download_ da última versão do pacote de distribuição do módulo **mod-sei-pen** para instalação ou atualização do sistema SEI. O pacote de distribuição consiste em um arquivo zip com a denominação mod-sei-pen-VERSAO.zip e sua última versão pode ser encontrada em https://github.com/spbgovbr/mod-sei-pen/releases

---

### 1.1.3. Descompactar o pacote de distribuição e atualizar os arquivos do sistema

Após realizar a descompactação do arquivo zip de instalação, será criada uma pasta contendo a seguinte estrutura:

```
/mod-sei-pen-VERSAO 
    /sei              # Arquivos do módulo posicionados corretamente dentro da estrutura do SEI
    /sip              # Arquivos do módulo posicionados corretamente dentro da estrutura do SIP
    INSTALACAO.md     # Instruções de instalação do mod-sei-pen
    ATUALIZACAO.md    # Instruções de atualização do mod**-sei-pen**    
    NOTAS_VERSAO.MD   # Registros de novidades, melhorias e correções desta versão
```

Importante enfatizar que os arquivos contidos dentro dos diretórios ```sei``` e ```sip``` não substituem nenhum código-fonte original do sistema. Eles apenas posicionam os arquivos do módulos nas pastas corretas de scripts, configurações e pasta de módulos; todos posicionados dentro de um diretório específico denominado mod-pen para deixar claro quais scripts fazem parte do módulo.

Os diretórios ```sei``` e ```sip``` descompactados acima devem ser mesclados com os diretórios originais através de uma cópia simples dos arquivos.

Observação: O termo curinga VERSAO deve ser substituído nas instruções abaixo pelo número de versão do módulo que está sendo instalado

```
cp /tmp/mod-sei-pen-VERSAO.zip <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
unzip mod-sei-pen-<VERSAO>.zip
```

---

### 1.1.4. Atualizar a base de dados do SIP com as tabelas do mod-sei-pen

A atualização realizada no SIP não cria nenhuma tabela específica para o módulo, apenas é aplicada a criação os recursos, permissões e menus de sistema utilizados pelo mod-sei-pen. Todos os novos recursos criados possuem o prefixo **pen_** para fácil localização pelas funcionalidades de gerenciamento de recursos do SIP.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php```

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

---

### 1.1.5. Atualizar a base de dados do SEI com as tabelas do mod-sei-pen

Nesta etapa é instalado/atualizado às tabelas de banco de dados vinculadas do mod-sei-pen. Todas estas tabelas possuem o prefixo **md_pen_** para organização e fácil localização no banco de dados.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php```

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

---

### 1.1.6. Verificação e testes da instalação

A partir da versão 2.0.0, o **mod-sei-pen** adiciona um novo script utilitário para que seja realizada uma verificação de todos os passos da instalação, assim como as configurações aplicadas. Estas verificações funcionam como um diagnóstico do correto funcionamento do sistema.

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

O resultado esperado para uma correta instalação e configuração do módulo é este apresentado abaixo:

```
INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN:
    - Arquivos do módulo posicionados corretamente
    - Módulo corretamente ativado no arquivo de configuracao do sistema
    - Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php
    - Verificada a compatibilidade do **mod-sei-pen** com a atual versão do SEI
    - Certificado digital localizado e corretamente configurado
    - Base de dados do SEI corretamente atualizada com a versão atual do mod-sei-pen
    - Conexão com o Tramita.GOV.BR realizada com sucesso
    - Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso

** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN** FINALIZADA COM SECESSO **
```


---
---



## 1.2. Atualização Completa (versão 1.5.x para 2.0.0)

Procedimentos para atualização do mod-sei-pen em versões iguais ou anteriores à **1.5.4**, necessitando passos adicionais para remoção de arquivos não mais necessários.

### Pré-requisitos
 - **Mod-Sei-Pen 1.5.4 ou versão inferior instalada**;
 - **SEI versão 3.1.x ou versão superior instalada**;
 - **Módulo **mod-sei-pen** previamente no SEI**
 - Usuário de acesso ao banco de dados do SEI e SIP com permissões para criar novas estruturas no banco de dados


### Procedimentos:

### 1.2.1 Fazer backup dos bancos de dados do SEI, SIP e dos arquivos de configuração do sistema.

Todos os procedimentos de manutenção do sistema devem ser precedidos de backup completo de todo o sistema a fim de possibilitar a sua recuperação em caso de falha. A rotina de instalação descrita abaixo atualiza tanto o banco de dados, como os arquivos pré-instalados do módulo e, por isto, todas estas informações precisam ser resguardadas.

---

### 1.2.2. Remover os arquivos desatualizados de versões anteriores

Para evitar a permanência de arquivos desatualizados de versões anteriores do  **mod-sei-pen**, sugerimos que o diretório do módulo seja removido, assim como os script de atualização do banco de dados do módulo:

O diretório de instalação da versão anterior está localizada em:

``` 
<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/web/modulos/pen
``` 

Os scripts de atualização do banco de dados estão em:

```bash 
rm <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/sei_atualizar_versao_modulo_pen.php
rm <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/sip_atualizar_versao_modulo_pen.php
``` 

E o script verifica-servico.sh localizado na pasta bin:

```bash 
rm <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/bin/verificar-servicos.sh
``` 

---

### 1.2.3. Desativar as configurações do SUPERVISOR e GEARMAN correspondente a versões anteriores

A versão 2.0.0 do **mod-sei-pen** fez uma reestruturação completa dos mecanismos de processamento de tarefas provenientes do Tramita.GOV.BR, mudança esta que fez o uso do SUPERVISOR e GEARMAN se tornarem opcional. 

Mesmo mantendo a utilização destes componentes, as mudanças aplicadas nesta versão modificaram os seus arquivos de configuração, sendo necessário desligar tais serviços e remover as configurações anteriores, conforme demonstrado abaixo:


Para desligar o SUPERVISOR, execute o seguinte comando:

```bash
gearadmin --shutdown
supervisorctl shutdown
``` 

Para resetar as configurações do Supervisor, execute o seguinte comando apontando para o seu arquivo de configuração.
PS: O arquivo de configuração do supervisor pode estar localizado em locais diferentes dependendo de sua distribuição. Verifique o local correto antes de executar este comando.

```bash
# Exemplo:
# Centos
echo_supervisord_conf > /etc/supervisor/supervisord.conf

# Ou

# RedHat
echo_supervisord_conf > /etc/supervisord.conf
``` 

---

### 1.2.4. Baixar o arquivo de distribuição do mod-sei-pen

Necessário realizar o _download_ da última versão do pacote de distribuição do módulo **mod-sei-pen** para instalação ou atualização do sistema SEI. O pacote de distribuição consiste em um arquivo zip com a denominação mod-sei-pen-VERSAO.zip e sua última versão pode ser encontrada em https://github.com/spbgovbr/mod-sei-pen/releases

---

### 1.2.5. Descompactar o pacote de distribuição e atualizar os arquivos do sistema

Após realizar a descompactação do arquivo zip de instalação, será criada uma pasta contendo a seguinte estrutura:

```
/mod-sei-pen-VERSAO 
    /sei              # Arquivos do módulo posicionados corretamente dentro da estrutura do SEI
    /sip              # Arquivos do módulo posicionados corretamente dentro da estrutura do SIP
    INSTALACAO.md     # Instruções de instalação do mod-sei-pen
    ATUALIZACAO.md    # Instruções de atualização do mod**-sei-pen**    
    NOTAS_VERSAO.MD   # Registros de novidades, melhorias e correções desta versão
```

Importante enfatizar que os arquivos contidos dentro dos diretórios ```sei``` e ```sip``` não substituem nenhum código-fonte original do sistema. Eles apenas posicionam os arquivos do módulos nas pastas corretas de scripts, configurações e pasta de módulos; todos posicionados dentro de um diretório específico denominado mod-pen para deixar claro quais scripts fazem parte do módulo.

Os diretórios ```sei``` e ```sip``` descompactados acima devem ser mesclados com os diretórios originais através de uma cópia simples dos arquivos.

Observação: O termo curinga VERSAO deve ser substituído nas instruções abaixo pelo número de versão do módulo que está sendo instalado

```
cp /tmp/mod-sei-pen-VERSAO.zip <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>
unzip mod-sei-pen-<VERSAO>.zip
```

---

### 1.2.6. Configurar os parâmetros do Módulo de Integração PEN

A nova versão do **mod-sei-pen** cria um arquivo de configuração específico para o módulo dentro da pasta de configuração do SEI (**<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/**). 

Com esta mudança, as configurações de integração não estão mais presente na página de configuração do módulo localizada em **SEI > Administração > Processo Eletrônico Nacional > Parâmetros de Configuração**. Portanto, a competência das parametrizações técnicas também não estará mais na responsabilidade do Perfil Administrador do sistema, mas sim, com a equipe de tecnologia da informação responsável pela atualização.

O arquivo de configuração padrão criado **ConfiguracaoModPEN.exemplo.php** vem com o sufixo **exemplo** justamente para não substituir o arquivo principal contendo as configurações vigentes do módulo.

Caso não exista o arquivo principal de configurações do módulo criado em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php**, renomeie o arquivo de exemplo para iniciar a parametrização da integração.

```
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/config/mod-pen/
cp ConfiguracaoModPEN.exemplo.php ConfiguracaoModPEN.php
```

Altere o arquivo de configuração específico do módulo em **<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/config/mod-pen/ConfiguracaoModPEN.php** e defina as configurações do módulo, conforme apresentado abaixo:

* **WebService**  
Endereço do Web Service principal de integração com o Tramita.GOV.BR. Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.api.processoeletronico.gov.br/interoperabilidade/soap/v3/
    * Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v2/
    * Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/soap/v3/ (novo - quem usa SEI já pode apontar pra cá. Quem usa rest é importante testar antes em homologação)


* **LocalizacaoCertificado**  
Localização completa do certificado digital utilizado para autenticação nos serviços do Tramita.GOV.BR. Os certificados digitais são disponibilizados pela equipe do Processo Eletrônico Nacional mediante aprovação do credenciamento da instituição. Verifique a seção [pré-requisitos](#pré-requisitos) para maiores informações.  
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
Endereço do Webservice de monitoramente de pendências de trâmite no Tramita.GOV.BR.
Configuração necessária somente quando o módulo é configurado para utilização conjunta com o Supervisor para monitorar ativamente todos os eventos de envio e recebimentos de processos enviados pelo Tramita.GOV.BR.  
Para maiores informações sobre como utilizar este recurso. Veja a seção [Conexão persistente com uso do Supervisor](#Conexão-persistente-com-uso-do-Supervisor) para maiores informações.  
Os endereços disponíveis são os seguintes (verifique se houve atualizações durante o procedimento de instalação):
    * Homologação: https://homolog.pendencias.processoeletronico.gov.br/
    * Produção: https://pendencias.conectagov.processoeletronico.gov.br/

---


### 1.2.7. Atualizar a base de dados do SIP com as tabelas do mod-sei-pen

A atualização realizada no SIP não cria nenhuma tabela específica para o módulo, apenas é aplicada a criação os recursos, permissões e menus de sistema utilizados pelo mod-sei-pen. Todos os novos recursos criados possuem o prefixo **pen_** para fácil localização pelas funcionalidades de gerenciamento de recursos do SIP.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php```

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

---

### 1.2.8. Atualizar a base de dados do SEI com as tabelas do mod-sei-pen

Nesta etapa é instalado/atualizado às tabelas de banco de dados vinculadas do mod-sei-pen. Todas estas tabelas possuem o prefixo **md_pen_** para organização e fácil localização no banco de dados.

O script de atualização da base de dados do SIP fica localizado em ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php```

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

---

#### 1.2.9. Reiniciar serviços de monitoramento de pendências de trâmite Gearman e Supervisor:

**Atenção!**
Necessário reiniciar os serviços de monitoramento de pendências de trâmite (Gearman e Supervisord) **SOMENTE SE** algum desses tenham sido instalados.

Reinicialização do Gearmand:

```bash
# CentOS, Redhat
systemctl restart gearmand

# Debian 
service gearman-job-server restart
```

Reinicialização do Supervisord:

```bash
supervisorctl reload
```

---

### 1.2.10. Configuração da periodicidade do agendamento de tarefas do SEI

A partir da versão SEI 3.1.x, o agendamento de tarefas do sistema pode ser executado em uma periodicidade de minutos, o que não era possível em versões anteriores (SEI 3.0.X). 

Com esta melhoria no SEI, o módulo mod-sei-pen 2.0.0 passou a utilizar o próprio agendamento de tarefas do sistema para realizar a consulta de novos processos no Tramita.GOV.BR, simplificando consideravelmente os procedimentos de instalação e configuração do módulo. 

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

### 1.2.11. Verificação e testes da instalação

A versão 2.0.0 do **mod-sei-pen** adiciona um novo script utilitário para que seja realizada uma verificação de todos os passos da instalação, assim como as configurações aplicadas. Estas verificações funcionam como um diagnóstico do correto funcionamento do sistema.

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

O resultado esperado para uma correta instalação e configuração do módulo é este apresentado abaixo:

```
INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN:
    - Arquivos do módulo posicionados corretamente
    - Módulo corretamente ativado no arquivo de configuracao do sistema
    - Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php
    - Verificada a compatibilidade do **mod-sei-pen** com a atual versão do SEI
    - Certificado digital localizado e corretamente configurado
    - Base de dados do SEI corretamente atualizada com a versão atual do mod-sei-pen
    - Conexão com o Tramita.GOV.BR realizada com sucesso
    - Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso

** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO **MOD-SEI-PEN** FINALIZADA COM SECESSO **
```

---
---

## 2. CONFIGURAÇÕES

Esta seção descreve os passos de configuração adicionais presentes na nova versão do módulo de Integração do SEI com o Tramita.GOV.BR. Todos os itens descritos nesta seção são destinados aos administradores do sistema SEI da instituição, responsáveis pela alteração de configurações gerais do sistema através do menu de administração do SEI (**SEI >> Administração >> Processo Eletrônico Nacional**)


### 2.1. Atribuição de Espécie Documental Padrão para Envio
A configuração de **Espécie Documental Padrão para Envio** define qual será o comportamento do sistema ao enviar processos que contenham Tipos de Documentos não mapeados previamente pelo Administrador. Neste caso, a espécie documental configurada será aplicada automaticamente, evitando que o trâmite seja cancelado pela falta desta configuração. 

O mapeamento de Espécie Documental Padrão para Envio deve ser feito através da funcionalidade:

    [SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > Envio > Botão "Atribuir Espécie Padrão"]

### 2.2. Atribuição de Tipo de Documento Padrão para Recebimento
 A configuração de Tipo de Documento Padrão para Recebimento define qual será o comportamento do sistema ao receber processos que contenham espécies documentais não mapeadas previamente pelo Administrador. Neste caso, o tipo de documento configurado será aplicado automaticamente, evitando que o trâmite seja cancelado pela falta de mapeamento.

O mapeamento de Tipo de Documento Padrão para Recebimento deve ser feito através da funcionalidade:

    [SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Tipos de Documentos > Recebimento > Botão "Atribuir Tipo de Documento Padrão"]

PS: Somente Tipos de Documento com aplicabilidade 'Externa' ou 'Interna e Externa' podem ser selecionados para esta configuração. 


### 2.3. Outras configurações

As demais configurações do **mod-sei-pen** podem ser encontradas nas seções descritas abaixo no arquivo de instalação do módulo, arquivo INSTALACAO.md presente no arquivo de distribuição do módulo (mod-sei-pen-VERSAO.zip).

* **Configuração**: 
Procedimentos destinados ao Administradores do SEI responsáveis pela configuração do módulo através da funcionalidades de administração do sistema.

* **Configurações Técnicas Adicionais**: 
Esta seção apresenta algumas configurações adicionais do módulo do Tramita.GOV.BR que não são obrigatórias para o funcionamento da integração, mas adicionam maior segurança, confiabilidade e desempenho ao módulo.

---
---

## 5. PROBLEMAS CONHECIDOS

Para maiores informações sobre problemas conhecidos e os procedimentos que devem ser feitos para corrigi-los, consulte a seção *PROBLEMAS CONHECIDOS* No arquivo ```INSTALACAO.md``` presente no arquivo de distribuição do módulo (mod-sei-pen-VERSAO.zip).

---
---

## 6. SUPORTE

Em caso de dúvidas ou problemas durante o procedimento de atualização, favor entrar em conta pelos canais de atendimento disponibilizados na Central de Atendimento do Processo Eletrônico Nacional, que conta com uma equipe para avaliar e responder esta questão de forma mais rápida possível.

Para mais informações, contate a equipe responsável por meio dos seguintes canais:
- [Portal de Atendimento (PEN): Canal de Atendimento](https://portaldeservicos.economia.gov.br) - Módulo do Barramento
- Telefone: 0800 978 9005

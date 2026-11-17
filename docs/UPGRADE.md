# Manual de Atualização do Módulo de Integração do Processo Eletrônico Nacional - PEN

O objetivo deste documento é descrever os procedimento para ATUALIZAÇÃO do Módulo de Integração com o Tramita.GOV.BR (**mod-sei-pen**) previamente instalado e configurado no Sistema Eletrônico de Informações (SEI).

**ATENÇÃO: Caso esta seja a primeira instalação do módulo no SEI, veja as instruções detalhadas de instalação no documento INSTALACAO.md presente no arquivo de distribuição do módulo (mod-sei-pen-VERSAO.zip)**

 
Para maiores informações, entre em contato pelo telefone 0800 978-9005 ou diretamente pela Central de Serviços do PEN, endereço http://processoeletronico.gov.br/index.php/assuntos/produtos/barramento


Este documento está estruturado nas seguintes seções:

1. **[Atualização](#atualização)**:  
Procedimentos para realizar a atualização de uma nova versão do módulo

    **[1.1. Atualização Simples (versão 2.0.x -> versão superior 2.0.x)](#)**  
    **[1.2. Atualização Completa (versão 1.5.x para 2.0.0)](#)**  
    **[1.4. Atualização para a 4.1.0 em instalações com Gearman](#)**  
    **[1.5. Verificação prévia dos anexos (4.1.0)](#)**  
    **[1.6. Agendamento de tarefas (4.1.0)](#)**  
    **[1.7. Atualização da 4.0.x para a 4.1.0 — ordem dos passos](#)**  
    **[1.8. Configuração para salvar LOGS do HTTP no SOLR](#18-configuração-para-salvar-logs-do-http-no-solr)**  

3. **[Configuração](#configuração)**:  
Procedimentos destinados ao Administradores do SEI responsáveis pela configuração do módulo através da funcionalidades de administração do sistema.

4. **[Suporte](#suporte)**:  
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

5. **[Problemas Conhecidos](#problemas-conhecidos)**:  
Canais de comunicação para resolver problemas ou tirar dúvidas sobre o módulo e os demais componentes do PEN.

---

## 1. ATUALIZAÇÃO

> ## ⚠️ Atualizando para a 4.1.0: backup do banco **e** do repositório de arquivos
>
> Esta versão **executa migração de dados**. Ela move as cópias imutáveis dos
> documentos tramitados da tabela `anexo` para a tabela do módulo, grava os
> arquivos numa árvore própria e **remove os originais**.
>
> **Antes de começar, faça backup dos dois, tirados no mesmo momento:**
>
> - o **banco de dados do SEI**;
> - o **repositório de arquivos** (`RepositorioArquivos`).
>
> Restaurar só um deles não recupera o sistema: o banco restaurado apontaria para
> arquivos que a limpeza já removeu, e os arquivos restaurados não teriam
> registro correspondente. **Os dois precisam voltar juntos, do mesmo instante.**
>
> Detalhes na seção **1.7**, que descreve a ordem completa dos passos.

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

> **Atualizando para a 4.1.0?** Esta versão executa migração de dados. Ao
> terminar, confira a linha `ignorados=N` na saída e guarde o arquivo indicado na
> última linha, se houver. Veja a seção **1.7**.

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
    * Homologação: https://homolog.api.processoeletronico.gov.br/interoperabilidade/rest/v4/
    * Produção: https://api.conectagov.processoeletronico.gov.br/interoperabilidade/rest/v4/

    Recomenda-se validar a configuração em homologação antes de apontar para produção.


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

> **Atualizando para a 4.1.0?** Esta versão executa migração de dados. Ao
> terminar, confira a linha `ignorados=N` na saída e guarde o arquivo indicado na
> última linha, se houver. Veja a seção **1.7**.

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

### 1.3. Arquivos brotli para SVG e JS e CSS

Foram adicionados arquivos comprimidos no formato **Brotli (`.br`)** para os recursos **SVG**, **JavaScript (JS)** e **CSS**.

Esses arquivos podem ser utilizados por servidores HTTP que oferecem suporte à compressão estática, permitindo que o conteúdo seja entregue diretamente na versão comprimida quando o cliente (navegador) informar suporte ao algoritmo Brotli por meio do cabeçalho `Accept-Encoding`.

### Benefícios

- Redução do tamanho dos arquivos transferidos.
- Melhor desempenho no carregamento das páginas.
- Menor consumo de largura de banda.
- Compatibilidade com navegadores modernos que suportam Brotli.

### Configuração no servidor HTTP

Para utilizar os arquivos `.br`, o servidor HTTP deve estar configurado para:

- Detectar o cabeçalho `Accept-Encoding` enviado pelo cliente.
- Servir automaticamente os arquivos `.br` correspondentes quando disponíveis.
- Enviar o cabeçalho `Content-Encoding: br` na resposta.
- Definir corretamente o tipo de conteúdo (`Content-Type`) para cada recurso.

Com essa configuração, os clientes compatíveis receberão automaticamente as versões compactadas dos arquivos **SVG**, **JS** e **CSS**, proporcionando uma entrega de conteúdo mais eficiente.

---

### 1.4. Atualização para a versão 4.1.0 em instalações com Gearman

**Só se aplica a quem usa Gearman.** Se o parâmetro `Gearman` do
`ConfiguracaoModPEN.php` está vazio, siga o procedimento normal.

### O que mudou

Até a 4.0.x, cada situação de trâmite era enfileirada numa função diferente
(`receberProcedimento`, `receberReciboTramite`, `receberTramitesRecusados`). A
chave única do Gearman impede processamento duplicado, mas **só dentro da mesma
função** — então um trâmite podia entrar por duas delas e ser processado em
paralelo. É a causa da issue #1180.

A 4.1.0 passa a usar uma função única, `processarPendencia`, e com isso o
próprio gearmand garante um trâmite por vez.



### Procedimento

1. Desative os agendamentos `PENAgendamentoRN::processarTarefasEnvioPEN` e
   `PENAgendamentoRN::processarTarefasRecebimentoPEN` em todos os nós.
2. Aguarde a fila esvaziar — `gearadmin --status`, com as colunas de fila e de
   execução zeradas em todas as funções.
3. Pare os workers em todos os nós.
4. Atualize **todos** os nós para a 4.1.0.
5. Suba os workers e reative os agendamentos.

### Conferência

`gearadmin --status` deve listar `processarPendencia` com workers disponíveis.
As três funções antigas continuam listadas — registradas de propósito — mas não
devem acumular fila.

O `verifica_instalacao_modulo_pen.php` deve reportar "Conexão com o servidor de
processamento de tarefas Gearman realizada com sucesso".


### ⚠️ Backup do banco e do repositório de arquivos — obrigatório antes da 4.1.0

**Faça backup do banco do SEI e do repositório de arquivos (`RepositorioArquivos`) antes da migração, tirados no mesmo momento.** A migração altera os dois: copia os anexos para `mod-pen/` e remove os registros antigos, e o agendamento de limpeza do SEI depois apaga os arquivos originais. Para voltar atrás é preciso restaurar ambos juntos — restaurar só o banco deixa registros apontando para arquivos que a limpeza já removeu.

> Não guarde o backup no mesmo volume do repositório: além de não proteger contra falha do disco, consome o espaço que a migração precisa (ver 1.5.1).


### 1.5. Verificação prévia dos anexos (recomendada)

A migração da 4.1.0 move os anexos de documentos internos de processos com **ao menos um trâmite concluído** (situação 6, recibo de conclusão recebido). Um anexo cujo arquivo esteja ausente, ilegível ou corrompido é **ignorado**: permanece como está, em `anexo`, e a migração segue — o id e o motivo de cada um são gravados em `<tmp>/migracao-anexos-ignorados-AAAAMMDD-HHMMSS-<pid>.log`, com um resumo no final. O caminho é informado na saída da migração, e o arquivo só é criado se houver algum anexo ignorado.

Saber disso **antes** da janela evita descobrir o tamanho do problema no meio dela, e é para isso que serve a verificação prévia.

O script `verifica_anexos_migracao_modulo_pen.php` percorre exatamente o mesmo conjunto que a migração percorreria e lista antes, em CSV, os anexos que dariam problema. É **somente leitura**: pode ser executado com o sistema no ar, dias antes da janela, quantas vezes for necessário.

> Em base grande a varredura gera carga de leitura no banco e no storage. Prefira executá-la **fora do horário de pico** (noite ou fim de semana), primeiro sem a conferência de hash. Pode ser interrompida com Ctrl+C a qualquer momento, sem efeito colateral — basta executar de novo.

**Pré-requisito: índices.** Na 4.0.x a tabela `md_pen_componente_digital` não tem os índices que a migração 4.1.0 cria. Sem eles a verificação varre a tabela inteira a cada lote e, em base grande, não termina — o script confere isso no início e para, mostrando os comandos. Crie-os antes, fora do horário de pico (mesma sintaxe nos quatro bancos):

```sql
CREATE INDEX i01_md_pen_comp_dig_anexo_imut ON md_pen_componente_digital (id_anexo_imutavel);
CREATE INDEX i02_md_pen_comp_dig_anexo      ON md_pen_componente_digital (id_anexo);
CREATE INDEX i03_md_pen_proc_eletr_proced   ON md_pen_processo_eletronico (id_procedimento);
```

> São os mesmos três que a migração 4.1.0 cria; ela reconhece o que já existe, pelo nome e pelas colunas, e segue. Criá-los antes só adianta o passo, e é o que permite rodar o verificador com a instalação ainda na 4.0.x. Não alteram dados. No MySQL o `i02` e o `i03` podem já existir com outro nome (índices das chaves estrangeiras) — nesse caso o script pede só o que faltar. Em Oracle, SQL Server e PostgreSQL a criação bloqueia gravações nessa tabela enquanto dura; o DBA pode usar a opção online do banco, se disponível.

Copie apenas esse arquivo para a instalação atual (ainda na 4.0.x), em `<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/scripts/mod-pen/`:

```bash
cd <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI>/sei/scripts/mod-pen
php -c /etc/php.ini verifica_anexos_migracao_modulo_pen.php
```

Ele solicita usuário e senha do banco do SEI, como o script de atualização, e grava a lista em `/tmp/verifica-anexos-AAAAMMDD-HHMMSS-<pid>.csv` — o caminho é exibido no início e no fim da execução. Para gravar em outra pasta, informe `TMPDIR` na chamada:

```bash
TMPDIR=/caminho/desejado php -c /etc/php.ini verifica_anexos_migracao_modulo_pen.php
```

> **Execute com o mesmo usuário que executará a migração.** O `root` lê qualquer
> arquivo, então anexos sem permissão de leitura passam despercebidos e só
> aparecem para a migração, que os ignora.

Para conferir também o conteúdo de cada arquivo (MD5), use `PEN_VERIFICA_HASH=1`. Esse modo lê todos os arquivos do repositório e, em base grande, pode levar horas.

Códigos de saída: `0` nenhum problema · `1` problemas encontrados · `3` varredura interrompida no limite de 10.000 problemas · `2` erro de execução, inclusive índices ausentes.

### 1.5.1. Espaço em disco necessário

Durante a migração cada anexo existe **em dois lugares**: o original no
repositório do SEI e a cópia em `mod-pen/`. A migração copia, confere o MD5 e só
então remove o registro antigo — o arquivo original permanece no disco até que o
agendamento de limpeza do SEI o recolha, o que pode levar dias.

Ambas as árvores ficam sob o mesmo `RepositorioArquivos`, portanto **no mesmo
volume**. Confirme a folga antes de iniciar.

#### 1. Quanto espaço e quantos inodes

O espaço ocupado **não** é a soma dos tamanhos: cada arquivo consome blocos
inteiros do sistema de arquivos (4 KB no ext4 padrão), e o desperdício do último
bloco se multiplica por milhões de arquivos.

A consulta abaixo roda sem alteração em MySQL, Oracle, SQL Server e PostgreSQL:

```sql
SELECT COUNT(*)                                     AS qtde_inodes,
       SUM(CAST(a.tamanho AS DECIMAL(20,0)))        AS bytes_logicos,
       SUM(CAST(a.tamanho AS DECIMAL(20,0)) + 4096) AS bytes_em_disco
  FROM anexo a
 INNER JOIN protocolo p ON p.id_protocolo = a.id_protocolo
 WHERE p.sta_protocolo = 'G'
   AND a.id_anexo IN (
         SELECT cd.id_anexo FROM md_pen_componente_digital cd
          WHERE cd.id_anexo IS NOT NULL
            AND EXISTS (SELECT 1 FROM md_pen_processo_eletronico pe
                         WHERE pe.id_procedimento = cd.id_procedimento
                           AND EXISTS (SELECT 1 FROM md_pen_recibo_tramite rt
                                        WHERE rt.numero_registro = pe.numero_registro))
   UNION SELECT cd.id_anexo_imutavel FROM md_pen_componente_digital cd
          WHERE cd.id_anexo_imutavel IS NOT NULL
            AND EXISTS (SELECT 1 FROM md_pen_processo_eletronico pe
                         WHERE pe.id_procedimento = cd.id_procedimento
                           AND EXISTS (SELECT 1 FROM md_pen_recibo_tramite rt
                                        WHERE rt.numero_registro = pe.numero_registro)));
```

`bytes_em_disco` é um **limite superior**: cada arquivo desperdiça no máximo um
bloco no final, então somar 4.096 por arquivo nunca subestima. O erro é sempre
para mais — no máximo 4 KB por arquivo —, que é o lado seguro no dimensionamento.

> O `CAST ... DECIMAL(20,0)` é necessário: no SQL Server, `SUM` de coluna `INT`
> devolve `INT` e a consulta falha com *Arithmetic overflow* quando o total passa
> de 2 GB — o que ocorre em qualquer base de produção.

Se o sistema de arquivos usar bloco diferente de 4 KB, troque o `4096`.
Descubra o valor com `stat -f -c %s <RepositorioArquivos>`.

#### 2. Confira os dois limites

| número | comparar com |
| - | - |
| `bytes_em_disco` | espaço livre: `df -h <RepositorioArquivos>` |
| `qtde_inodes` | inodes livres: `df -i <RepositorioArquivos>` |

São limites **independentes**. Uma base pode ter espaço de sobra em bytes e
mesmo assim não conseguir criar os arquivos por falta de inodes — e isso não
aparece no `df -h`. Faltar qualquer um dos dois interrompe a migração (já ocorreu
em teste, no lote 85 de 2.001, por disco cheio). Deixe margem nos dois.

#### 3. Se o espaço for insuficiente

A migração é **retomável**, o que permite fazê-la em etapas:

1. execute a migração até o disco apertar, ou interrompa deliberadamente;
2. pare a migração;
3. execute o agendamento `AgendamentoRN::removerAquivosExternosExcluidos` do SEI,
   que remove do repositório os arquivos cujo registro em `anexo` já não existe —
   exatamente os que acabaram de ser migrados;
4. com o espaço liberado, reexecute `sei_atualizar_versao_modulo_pen.php`, que
   retoma de onde parou;
5. repita até concluir.

> **A limpeza precisa rodar entre as etapas, com a migração parada.** Em paralelo,
> ela pode remover arquivo cujo registro ainda não foi confirmado no banco.

### 1.6. Atualização para a versão 4.1.0 — agendamento de tarefas

**Aplica-se a toda atualização para a 4.1.0.** Esta versão executa a migração
dos anexos de documentos internos, que move registros e arquivos enquanto roda.

O SEI e o módulo possuem rotinas agendadas de limpeza de arquivos que atuam
sobre os mesmos anexos. Para evitar interferência, mantenha-as paradas durante
o procedimento.

### Procedimento

1. Desative o agendamento `AgendamentoRN::removerAquivosNaoUtilizados` em
   Administração → Infraestrutura → Agendamento de Tarefas.
2. Pare o agendador de tarefas do SEI.
3. Execute a atualização normalmente.
4. Após a migração concluir, religue o agendador e reative o agendamento.

> Em caso de erro de limite de transação durante a migração, é possível
> reduzir o tamanho do lote (padrão 500) definindo a variável de ambiente
> `PEN_MIGRACAO_ANEXOS_LOTE` ao executar o script:
>
> ```bash
> PEN_MIGRACAO_ANEXOS_LOTE=200 php -c /etc/php.ini <DIRETÓRIO RAIZ>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
> ```

> O script de atualização cadastra rotinas próprias de limpeza do módulo
> (`PENAgendamentoRN::removerArquivosExcluidosModSeiPen` e
> `PENAgendamentoRN::removerArquivosNaoUtilizadosModSeiPen`), ativas a partir da
> instalação. Com o agendador parado, elas só entram em operação depois que a
> migração terminar.

### 1.7. Atualização da 4.0.x para a 4.1.0 — ordem dos passos

Esta versão executa **migração de dados**: move as cópias imutáveis dos documentos
tramitados da tabela `anexo` do SEI para a tabela `md_pen_anexo_documento` do
módulo, com árvore de arquivos própria. Os passos específicos estão detalhados
nas seções anteriores; esta é a ordem em que devem ser executados.

**Dias antes da janela**

1. **Crie os três índices** (seção 1.5). A verificação prévia exige, e a migração
   os reaproveita.
2. **Execute a verificação prévia dos anexos** (seção 1.5). É somente leitura e
   roda com o sistema no ar. O CSV gerado lista os anexos cujos arquivos estão
   ausentes, ilegíveis ou corrompidos, com o número do processo e do documento.
3. **Confira o espaço em disco** (seção 1.5.1). A migração copia antes de apagar,
   então o repositório cresce durante o procedimento.
4. **Trate os anexos apontados**, se houver, ou aceite que ficarão para trás.

**Na janela, antes de executar**

5. ⚠️ **Faça backup do banco de dados do SEI E do repositório de arquivos**,
   tirados no mesmo momento. É o passo mais importante de todo o procedimento:
   a migração remove os registros e os arquivos originais, e voltar atrás exige
   restaurar **os dois juntos, do mesmo instante**. Restaurar só o banco deixa
   registros apontando para arquivos que já não existem.
6. **Coloque o sistema fora do ar.**
7. **Pare os workers do Gearman**, se a instalação os usa (seção 1.4).
8. **Pare o agendador de tarefas do SEI** e desabilite o agendamento
   `AgendamentoRN::removerAquivosNaoUtilizados` (seção 1.6).

**Execução**

9. **Atualize os arquivos e execute os scripts de atualização** do SIP e do SEI,
   seguindo o roteiro da seção 1.1.
10. **Ao terminar, confira a linha `ignorados=N`** na saída da migração. Se for
    maior que zero, guarde o arquivo indicado na última linha — é a lista dos
    anexos que permaneceram em `anexo` e precisam de tratamento.

**Depois**

11. **Reative o agendador de tarefas** e, se aplicável, religue os workers do
    Gearman — estes só depois que todos os nós de aplicação estiverem na 4.1.0.
12. **Verifique a instalação** com o `verifica_instalacao_modulo_pen.php`.

> A migração é **retomável**: se for interrompida, executar o script novamente
> continua de onde parou. Anexo com arquivo problemático não interrompe o
> procedimento — é ignorado, e a migração segue.

---

### 1.8. Configuração para salvar LOGS do HTTP no SOLR

Desde a versão 4.1.0 é possível salvar os logs do HTTP para debug e coleta de informações para abertura de chamados.

Para salvar os logs de HTTP é necessário ter o solr configurado no SEI e criar um core específico para o módulo do Tramita.

Para Solr com usuário adequado (geralmente solr):

```
$ mkdir -p <PASTADECORESDOSOLR>/mod-sei-pen
$ cp <PASTADESCOMPACTADADOMODULO>/solr <PASTADECORESDOSOLR>/mod-sei-pen/conf
$ SOLR_AUTH_TYPE="basic" -e SOLR_AUTHENTICATION_OPTS="-Dbasicauth=LOGINADMINDOSOLR:SENHAADMINDOSOLR" <CAMINHODO>/bin/solr create -c mod-sei-pen -d <PASTADECORESDOSOLR>/mod-sei-pen/conf
```
---

Depois de criado o core deve-se habilitar criando um parâmetro **MOD_SEI_PEN_SALVA_HTTP_LOGS** com valor '1'. Para desabilitar colocar o valor '0'.

> [!IMPORTANT]
> Não use esse parâmetro com valor '1' por muito tempo em produção pois pode degradar o ambiente. Só use para debug quando necessário.

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

### 2.4. Envio para Múltiplos Órgãos

A parametrização **`EnvioMultiplosOrgaos`** define a configuração utilizada para o envio de um mesmo processo para múltiplos órgãos.

Por padrão, essa parametrização não possui órgãos configurados:

```php
"EnvioMultiplosOrgaos" => array(),
```
#### Exemplo

```php
"EnvioMultiplosOrgaos" => array(
   "12345", "54321", "9876"
),
```

Nesse exemplo, o processo deverá ser encaminhado para os três órgãos configurados:

1. `12345`
2. `54321`
3. `9876`


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

# Configuração do Ambiente de Desenvolvimento módulo mod-sei-pen

Este documento descreve os passos necessários para configurar um ambiente de desenvolvimento do projeto **mod-sei-pen** para permitir colaborações no projeto.

## 1 - Configurar comitês de protocolo
Para colaborar com o desenvolvimento e testes do módulo ```mod-sei-pen```, será necessário configurar dois comitês de protocolo no ambiente de homologação Tramita.GOV.BR. Este passo é necessário para que possa ser emulado duas diferentes entidades durante os  trâmite de processos.

Em casos específicos, apenas um comitê de protocolo de homologação poderá ser utilizado de forma  limitada. Neste caso, o ambiente de homologação público, disponíbilizado pelo Ministério da Economia, poderá ser utilizado para simular o trâmite de processo entre dois sistemas. Esse ambiente encontra-se disponível em https://sei-pen-mp.hom.nuvem.gov.br.

Para realizar a configuração do comitê de protocolo para desenvolvimento, acesse o ambiente de homologação do PEN disponível em https://homolog.gestaopen.processoeletronico.gov.br/

Exemplo:
- Comitê de Protocolo 1: 
	Órgão de Desenvolvimento ABC - ABC
  		Unidade de Desenvolvimento ORG1 - ABC-ORG1 
- Comitê de Protocolo 2:
	Órgão de Desenvolvimento XYZ - XYZ
		Unidade de Desenvolvimento ORG2 - XYZ-ORG2 


## 2 - Cadastrar sistemas responsáveis pelo envio/recebimento de processos das unidades
Após finalizado o passo anterior, será necessário cadastrar o sistema que será responsável por enviar e receber processos para cada um dos comitês. É necessário o cadastro de dois diferentes sistemas para simular o envio de um processo e o recebimento do mesmo em ambiente local de desenvolvimento.

Exemplo:
- Sistema ABC => Órgão de Desenvolvimento ABC - ABC
- Sistema XYZ => Órgão de Desenvolvimento XYZ - XYZ
	
## 3 - Vincular os sistemas às respectivas unidades ao qual serão responsáveis pelo envio/recebimento
Realizado o cadastro dos sistemas de protocolo, será necessário fazer a sua vinculação aos respectivos comitês de protocolo. Acesse a funcionalidade **[Protocolo > Comitês de Protocolo]**, localize os comitês previsamente cadastrados (ABC ou XYZ), clique no botão **[Sistemas]** e faça a devida vinculação.

Exemplo:
- Órgão de Desenvolvimento ABC => Sistema ABC
- Órgão de Desenvolvimento XYZ => Sistema XYZ


## 4 - Vincular unidades administrativas ao sistema do comitê de protocolo
Após a vinculação dos sistemas aos seus respectivos comitês de protocolo, agora será necessário configurar quais as unidades administrativas estes sistemas são responsáveis por enviar e receber processos.

Para isto, acesse a funcionalidade **[Protocolo > Comitês de Protocolo]**, localize os comitês previsamente cadastrados (ABC ou XYZ), clique no botão **[Sistemas]**, onde será listado os sistemas vinculados. Selecione o sistema correto e clique em Unidades Administrativas.

Na página que irá se abrir, seleciona as unidades ao qual tal sistema será responsável por enviar e receber processos.
	
## 5 - Gerar novos certificados digitais de autentição dos sistemas.
Para que os novos sistemas cadastrados possam interagir com o Tramita.GOV.BR, é necessário a geração de certificados digitais de autenticação para ambos. Estes deverão ser armazenados de forma segura pelo desenvolvedor e posteriormente configurados no ambiente de desenvolvimento.

Para gerar os novos certificados, acesse **[Administração > Sistemas de Processo Eletrônico]**, encontre o respectivo sistema e clique em **[Gerar Certificado]**. Será apresentado uma página informando qual a senha de acesso e o botão para baixar o certificado gerado. Guarde estas duas informações para configuração posterior no ambiente de desenvolvimento. 

> Atenção: Os certificados não permitem a alteração de suas senhas por questões de segurança. Caso ocorra sua perda, será necessário gerar novos certificados. 
	

## 6 - Configurar certificados digitais de org1 e org2 no ambiente de desenvolvimento
O ambiente de desenvolvimento possui a configuração de duas instâncias do sistema (docker-compose) para representar cada um dos comitês de protocolo necessários para testes e desenvolvimento das funcionalidades do módulo. As duas instâncias pré-configuradas são chamadas Org1 e Org2.

Com o certificado e senha em mãos, será necessário configurar cada uma destas instâncias através do arquivo de variável de ambiente .env, parâmetros ```ORG1_CERTIFICADO_SENHA``` e ```ORG2_CERTIFICADO_SENHA```.  Este arquivo fica localizado na pasta de testes do módulo: ```tests/funcional```.

Exemplo: 
```
ORG1_CERTIFICADO=/opt/sei/config/mod-pen/certificado_org1.pem
ORG1_CERTIFICADO_SENHA=XXXXXXXXXXXXXX

ORG2_CERTIFICADO=/opt/sei/config/mod-pen/certificado_org2.pem
ORG2_CERTIFICADO_SENHA=XXXXXXXXXXXXXX
```

Depois de configurar a senha, copie os respectivos arquivos do certificado para pasta arquivos de configuração de testes (```<PASTA DO PROJETO>/src/sei/web/modulos/mod-sei-pen/tests/funcional/assets/config```). Sugerimos modificar o nome dos arquivos para **certificado_org1.pem** e **certificado_org1.pem** para manter a configuração padrão e não necessitar modificar os parâmetros ```ORG1_CERTIFICADO``` e ```ORG2_CERTIFICADO``` nos arquivos de variável de ambiente.


## 7 - Inicializar instâncias org1 e org2 de desenvolvimento
Para iniciarlizar o ambiente de desenvolvimento local, certifique-se que o projeto **mod-sei-pen** está localizado na pasta de módulos do sistema. 

Nesta pasta execute os seguintes comandos MAKE:

``` 
$ make up             # Inicializa o ambiente do sistema utilizando docker
$ make install        # Instala o módulo mod-sei-pen e atualiza o bando de dados
```

> Atenção: Caso o docker instalado na máquina de desenvolvimento exija que seja executado como root através do comando SUDO, informe-o antes das instruções mencionadas acima (ex: ```sudo make up```)

## 8 - Verificar se todas as configurações do módulo estão corretas
O **mod-sei-pen** possui um script de verificação da instalação, avaliando as parametrizações e simulando conexões com o Barramento do PEN para validar as permissões de autenticação. Para realizar esta verificação, execute o comando:

```
$ make verify-config
```

O resultado esperado é o seguinte

```
00001 -     INICIANDO VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN:
00002 -         - Arquivos do módulo posicionados corretamente
00003 -         - Módulo corretamente ativado no arquivo de configuracao do sistema
00004 -         - Parâmetros técnicos obrigatórios de integração atribuídos em ConfiguracaoModPEN.php
00005 -         - Verificada a compatibilidade do mod-sei-pen com a atual versão do SEI
00006 -         - Certificado digital localizado e corretamente configurado
00007 -         - Base de dados do SEI corretamente atualizada com a versão atual do mod-sei-pen
00008 -         - Conexão com o Tramita.GOV.BR realizada com sucesso
00009 -         - Acesso aos dados do Comitê de Protocolo vinculado ao certificado realizado com sucesso
00010 -     
00011 -     ** VERIFICAÇÃO DA INSTALAÇÃO DO MÓDULO MOD-SEI-PEN FINALIZADA COM SUCESSO **
```

## 9 - Configuração dos hostnames org1-http e org2-http
Após a execução de todos os passos acima, duas instâncias do sistema estarão em execução na máquina de desenvolvimento, atendendo requisições em dois endereços diferentes: 

```
ORG1: http://org1-http:8000/sei
ORG2: http://org2-http:8000/sei
```

Para que estes endereços sejam reconhecidos, será necessário adicionar duas entradas no arquivo ```/etc/hosts``` (linux). Adicione as linhas abaixos no final do arquivo mencionado:

```
127.0.0.1	org1-http
127.0.0.1	org2-http

```  


## 10 - Configuração no sistema: Configuração de Parâmetros de Administração
Finalizado a instalação, será necessário realizar as configurações finais para a correta vinculação com os comitês de protocolos criados nas etapas iniciais.

Acesse **[SEI > Administração > Processo Eletrônico Nacional > Configuração de Parâmetros]** e preencha os campos de acordo com a configuração realizada no Portal do Barramento do PEN para sua instituição:

* **Repositório de Estruturas**: Repositório de estrutura ao qual seu comitê de protocolo foi criado
Exemplo: Poder Executivo Federal

* **Tipo de Processo Externo**: Identificação do Tipo de Processo que será aplicado à todos os processos e documentos recebidos de outras instituições pelo Tramita.GOV.BR.  


* **Unidade SEI para Representação de Órgãos Externos**
*Identificação da unidade administrativa que representará órgãos e unidades externas nos históricos de andamento do processo. 
Exemplo: TESTE_1_2

> ATENÇÃO: Repita os passos anteriores para a instância correspondente ao ORG2, informando os dados corretos de acordo com os cadastros realizados no ambiente de homologação do Barramento do PEN.


## 11 - Configuração no sistema: Configuração de Mapeamento de unidades 

Finalizado a configuração geral no passo anterior, será necessário mapear as unidades do sistema para aquelas previamente liberadas para envio e recebimento de processos.

Para isto, acesse **[SEI > Administração > Processo Eletrônico Nacional > Mapeamento de Unidades]** e mapeie as unidades da base de teste (TESTE, TESTE_1_1) para a Unidade Administrativa do comitê respectivo. 

Exemplo:
- Órgão de Desenvolvimento ABC - ABC
	Unidade de Desenvolvimento ORG1 - ABC-ORG1 
- Órgão de Desenvolvimento XYZ - XYZ
	Unidade de Desenvolvimento ORG2 - XYZ-ORG2 


> ATENÇÃO: Repita os passos anteriores para a instância correspondente ao ORG2, informando os dados corretos de acordo com os cadastros realizados no ambiente de homologação do Barramento do PEN.


## FINAL - CONFIGURAÇÃO DO AMBIENTE DE DESENVOLVIMENTO MOD-SEI-PEN
Ao final da configuração de todos os passos anteriores, duas instâncias do sistema estarão rodando na máquina de desenvolvimento, cada uma representando um comitê de protocolo diferente.

Endereços de acesso:
ORG1: http://org1-http:8000/sei
ORG2: http://org2-http:8000/sei


Agora, testes de trâmite de processos podem ser realizados entre a instância org1 e Instância org2, sendo possível analizar e "debugar" todo o comportamento do módulo, tanto para envio, como para recebimento. 


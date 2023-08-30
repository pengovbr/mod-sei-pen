# Configuração dos Testes

Este documento vai focar nos testes funcionais. Assim que evoluirmos os testes unitários e ou integração, referenciaremos os mesmos aqui.

## Visão Geral dos Testes Funcionais

Os testes estão escritos em Phpunit e rodam via Selenium.
Como está tudo em container não há a necessidade de instalar o Selenium em sua máquina.

O projeto Makefile se encarrega de chamar os containeres necessários para a execução dos testes que ocorrem em segundo plano.

Caso queira visualizar os testes em tempo de execução basta conectar seu cliente VNC favorito ao conteiner.


## Abrangência dos Testes

Os cenários estão construídos para representar diversas situações do mundo real. Temos cenários tanto para os casos simples como envio e recebimento de processo, como para casos de simulação de erros ou excessões. Por ex: o órgão A envia um conteúdo não permitido para o órgão B e verifica se o mesmo recusou com sucesso.

Como são muitas situações a serem cobertas o teste funcional demora a ser concluído.

O teste funcional deve ser executado sempre antes de algum release e deve percorrer todas as bases de dados suportadas pelo PEN e também todas as versões anteriores dos Sistemas de Processo Eletrônico por ele suportada.

Ao implementar nova funcionalidade é altamente recomendável que se crie novo teste ou adapte algum existente para testar as situações desejadas.


## Configuração do Ambiente para Rodar os Testes Funcionais

### Pré-requisitos
- configure seu ambiente como descrito em: [Configuração do Ambiente de Desenvolvimento](DESENV.md)

- teste manualmente um trâmite simples do seu sistema 1 para o sistema 2 - isso já vai estar acontecendo caso a configuração anterior tenha ocorrido com sucesso

- para o atual escopo dos testes: o órgão 1 precisa de duas unidades mapeadas lá no barramento com direito a enviar e receber processos; o órgão 2 precisa de apenas uma unidade mapeada para enviar e receber processos

- o passo a passo abaixo elenca como habilitar os testes para o SEI4, para os outros sistemas basta seguir de forma análoga


**Importante:** o teste foi feito para configurar o ambiente automaticamente de acordo com as variáveis informadas nos arquivos de configuração do projeto: comitês, unidades de envio e recebimento, tipos de documento, etc. Desta forma é aconselhável rodar os testes sempre em um ambiente novo. Caso contrário o teste poderá bagunçar as configurações que já existam naquele ambiente. Tenha isso em mente ao rodar os testes em ambientes pré-configurados.

### 1. Escolha do sistema a ser testado

Abra o Makefile e procure a variável "sistema" para indicar qual sistema será testado (sei3, sei4 ou super):
```
sistema=super
```


### 2. Cópia do certificado

*Passo já executado no manual do desenvolvimento*

Vá até a pasta tests_sei4/funcional/assets/config/
Copie seus certifcados para os dois orgaos.
Cole-os com o nome certificado_org1.pem e certificado_org2.pem


### 3. Escolha da base de dados e ajustes do arquivo .env

*Passo já executado no manual do desenvolvimento.
Aqui apenas algumas informações adicionais para multibase e multiversões* 

Agora vamos criar um arquivo .env para cada base de dados que se deseja rodar os testes.

Vá até a pasta tests_sei4/funcional

Nessa pasta existe um modelo .env para cada base de dados suportada.
Copie cada um deles e altere-os de acordo com a senha do seu certificado.
Você pode usar o mesmo certificado (org1 e org2) em cada uma das bases de dados, desde que não suba simultaneamente. Caso contrário vai ocorrer falha pois teremos mais de um sistema capturando os mesmos processos.

Pontos a se alterar em cada .env:
``` 
SEI_PATH=../../../../../../
ORG1_CERTIFICADO_SENHA=XXX
ORG2_CERTIFICADO_SENHA=XXX
```
A varável SEI_PATH pode conter o caminho absoluto ou relativo do código fonte do sistema.
Como vamos testar várias versões do mesmo sistema então de acordo com o seu fluxo de teste basta mudar ai ou criar vários .env com a informação que se deseja.

Escolha um arquivo env com as suas modificações  e renomeie-o para .env


### 4. Ajuste do arquivo phpunit.xml de acordo com os seus ambientes

O arquivo phpunit.xml contém diretivas acerca da execução dos testes. Por ex: pra quem o teste vai tramitar um processo? Qual a url do sistema que vai receber? etc

Abra o arquivo: tests_sei4/funcional/phpunit.xml

Existem diversas diretivas aqui que podem ser alteradas, vamos elencar apenas as principais para que você consiga rodar os testes:

```
<const name="CONTEXTO_ORGAO_A_NUMERO_SEI" value="XXX" />
<const name="CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS" value="XXX" />
<const name="CONTEXTO_ORGAO_A_REP_ESTRUTURAS" value="XXX" />
<const name="CONTEXTO_ORGAO_A_ID_ESTRUTURA" value="XXX" />
<const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA" value="" />
<const name="CONTEXTO_ORGAO_A_NOME_UNIDADE" value="XXX" />    
<const name="CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA" value="XXX" />    
<const name="CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA" value="XXX" />
<const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA" value="XXX" />

<const name="CONTEXTO_ORGAO_B_NUMERO_SEI" value="YYY" />    
<const name="CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS" value="ZZZ" />
<const name="CONTEXTO_ORGAO_B_REP_ESTRUTURAS" value="ZZZ" />
<const name="CONTEXTO_ORGAO_B_ID_ESTRUTURA" value="ZZZ" />
<const name="CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA" value="" />
<const name="CONTEXTO_ORGAO_B_NOME_UNIDADE" value="ZZZ" />

```
Onde tem XXX, YYY e ZZZ deve ser substituído com os seus valores. Esses valores você consegue no portal do barramento. Verifique o manual de configuração do desenvolvedor.

O número SEI é um número ao seu gosto com 3 dígitos e que não pode repetir para o ORGAO_A e ORGAO_B.

Abaixo um arquivo inteiro de um orgao que tramita em homologação, serve para exemplo:

```
<?xml version="1.0" encoding="UTF-8"?>

<phpunit 
  bootstrap="bootstrap.php" 
  backupGlobals="true" 
  colors="true" 
  verbose="true"
  cacheResult="false"
  executionOrder="no-depends"
  >
  
  <php>
    <const name="PHPUNIT_HOST" value="selenium"/>
    <const name="PHPUNIT_PORT" value="4444"/>  
    <const name="PHPUNIT_BROWSER" value="chrome"/>
    <const name="PHPUNIT_TESTS_URL" value="http://localhost/sei"/>
    <const name="PEN_ENDERECO_WEBSERVICE" value="https://homolog.api.processoeletronico.gov.br/interoperabilidade/soap/v3/?wsdl"/>

    <!-- Chaves de configurações gerais do teste do Tramita.GOV.BR -->
    <const name="PEN_WAIT_TIMEOUT" value="40000" /> 
    <const name="PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES" value="120000" /> 
    <const name="PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE" value="120000"/> 
    <const name="PEN_SCRIPT_MONITORAMENTO_ORG1" value=" "/> 
    <const name="PEN_SCRIPT_MONITORAMENTO_ORG2" value=" "/> 

    <!-- Chaves de configuração dos diferentes ambientes envolvidos no teste do Tramita.GOV.BR -->
    <!-- CONFIGURAÇÕES DE TESTE ÓRGÃO 1 -->
    <const name="CONTEXTO_ORGAO_A" value="CONTEXTO_ORGAO_A" /> 
    <const name="CONTEXTO_ORGAO_A_URL" value="http://org1-http:8000/sei"/>
    <const name="CONTEXTO_ORGAO_A_SIGLA_ORGAO" value="ABC" />
    <const name="CONTEXTO_ORGAO_A_NUMERO_SEI" value="779" />
    <const name="CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS" value="5" />
    <const name="CONTEXTO_ORGAO_A_REP_ESTRUTURAS" value="RE CGPRO" />
    <const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE" value="TESTE" />
    <const name="CONTEXTO_ORGAO_A_ID_ESTRUTURA" value="145856" />
    <const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA" value="" />
    <const name="CONTEXTO_ORGAO_A_NOME_UNIDADE" value="Orgao Jenkins Integracao 3 sqlServer" />    
    <const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA" value="TESTE_1_1" />    
    <const name="CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA" value="145860" />    
    <const name="CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA" value="Jenkins sqlServer Unidade 2" />
    <const name="CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA" value="JSU2" />
    <const name="CONTEXTO_ORGAO_A_USUARIO_LOGIN" value="teste" />
    <const name="CONTEXTO_ORGAO_A_USUARIO_SENHA" value="teste" />    
    <const name="CONTEXTO_ORGAO_A_TIPO_PROCESSO" value="Arrecadação: Cobrança" />
    <const name="CONTEXTO_ORGAO_A_TIPO_PROCESSO_SIGILOSO" value="Acesso à Informação: Demanda do e-SIC" />
    <const name="CONTEXTO_ORGAO_A_TIPO_DOCUMENTO" value="Ofício" />
    <const name="CONTEXTO_ORGAO_A_TIPO_PROCESSO_SIGILOSO" value="Acesso à Informação: Demanda do e-SIC" />
    <const name="CONTEXTO_ORGAO_A_TIPO_DOCUMENTO_NAO_MAPEADO" value="Voto" />
    <const name="CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO" value="Documento Preparatório (Art. 7º, § 3º, da Lei nº 12.527/2011)" />
    <const name="CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO_NAO_MAPEADO" value="Informação Pessoal (Art. 31 da Lei nº 12.527/2011)" />
    <const name="CONTEXTO_ORGAO_A_CARGO_ASSINATURA" value="Assessor(a)" />       
    <const name="CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO_PADRAO" value="Controle Interno (Art. 26, § 3º, da Lei nº 10.180/2001)" />
    <const name="CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO_INATIVA" value="Situação Econômico-Financeira de Sujeito Passivo (Art. 198, caput, da Lei nº 5.172/1966 - CTN)" />
    <const name="CONTEXTO_ORGAO_A_HIPOTESE_SIGILOSO" value="Sigilo do Inquérito Policial (Art. 20 do Código de Processo Penal)" />

    <!-- CONFIGURAÇÕES DE TESTE ÓRGÃO 2 -->
    <const name="CONTEXTO_ORGAO_B" value="CONTEXTO_ORGAO_B" />
    <const name="CONTEXTO_ORGAO_B_URL" value="http://org2-http:8000/sei"/>
    <const name="CONTEXTO_ORGAO_B_SIGLA_ORGAO" value="ABC" />
    <const name="CONTEXTO_ORGAO_B_NUMERO_SEI" value="159" />    
    <const name="CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS" value="5" />
    <const name="CONTEXTO_ORGAO_B_REP_ESTRUTURAS" value="RE CGPRO" />
    <const name="CONTEXTO_ORGAO_B_SIGLA_UNIDADE" value="TESTE" />
    <const name="CONTEXTO_ORGAO_B_ID_ESTRUTURA" value="145857" />
    <const name="CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA" value="" />
    <const name="CONTEXTO_ORGAO_B_NOME_UNIDADE" value="Orgao Jenkins Integracao 4 sqlServer" />
    <const name="CONTEXTO_ORGAO_B_USUARIO_LOGIN" value="teste" />
    <const name="CONTEXTO_ORGAO_B_USUARIO_SENHA" value="teste" />    
    <const name="CONTEXTO_ORGAO_B_ID_ESTRUTURA_SECUNDARIA" value="XXXXXXXXXXXXXX" />    
    <const name="CONTEXTO_ORGAO_B_NOME_UNIDADE_SECUNDARIA" value="XXXXXXXXXXXXXX" />  
    <const name="CONTEXTO_ORGAO_B_SIGLA_UNIDADE_SECUNDARIA" value="" />
    <const name="CONTEXTO_ORGAO_B_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA" value="" />
    <const name="CONTEXTO_ORGAO_B_TIPO_PROCESSO" value="Arrecadação: Cobrança" />    
    <const name="CONTEXTO_ORGAO_B_TIPO_PROCESSO_SIGILOSO" value="Acesso à Informação: Demanda do e-SIC" /> 
    <const name="CONTEXTO_ORGAO_B_TIPO_DOCUMENTO" value="Ofício" />
    <const name="CONTEXTO_ORGAO_B_TIPO_DOCUMENTO_NAO_MAPEADO" value="Nota" />  
    <const name="CONTEXTO_ORGAO_B_TIPO_PROCESSO_SIGILOSO" value="Acesso à Informação: Demanda do e-SIC" />          
    <const name="CONTEXTO_ORGAO_B_HIPOTESE_RESTRICAO" value="Documento Preparatório (Art. 7º, § 3º, da Lei nº 12.527/2011)" />    
    <const name="CONTEXTO_ORGAO_B_HIPOTESE_RESTRICAO_NAO_MAPEADO" value="Informação Pessoal (Art. 31 da Lei nº 12.527/2011)" />
    <const name="CONTEXTO_ORGAO_B_HIPOTESE_RESTRICAO_INATIVA" value="Situação Econômico-Financeira de Sujeito Passivo (Art. 198, caput, da Lei nº 5.172/1966 - CTN)" />
    <const name="CONTEXTO_ORGAO_B_HIPOTESE_SIGILOSO" value="Sigilo do Inquérito Policial (Art. 20 do Código de Processo Penal)" />    
    <const name="CONTEXTO_ORGAO_B_CARGO_ASSINATURA" value="Assessor(a)" />       
    <const name="CONTEXTO_ORGAO_B_HIPOTESE_RESTRICAO_PADRAO" value="Controle Interno (Art. 26, § 3º, da Lei nº 10.180/2001)" />
    

    <!-- CONFIGURAÇÕES DE TESTE Órgão 3, caso de sem hierarquia pai -->
    <const name="CONTEXTO_ORGAO_C" value="CONTEXTO_ORGAO_C" />
    <const name="CONTEXTO_ORGAO_C_URL" value="http://xxxxx/sei"/>
    <const name="CONTEXTO_ORGAO_C_SIGLA_ORGAO" value="ABC" />
    <const name="CONTEXTO_ORGAO_C_NUMERO_SEI" value="159" />    
    <const name="CONTEXTO_ORGAO_C_ID_REP_ESTRUTURAS" value="5" />
    <const name="CONTEXTO_ORGAO_C_REP_ESTRUTURAS" value="RE CGPRO" />
    <const name="CONTEXTO_ORGAO_C_SIGLA_UNIDADE" value="TESTE" />
    <const name="CONTEXTO_ORGAO_C_ID_ESTRUTURA" value="121390" />
    <const name="CONTEXTO_ORGAO_C_SIGLA_UNIDADE_HIERARQUIA" value="" />
    <const name="CONTEXTO_ORGAO_C_NOME_UNIDADE" value="SEGES TESTE SEM PAI" />
    <const name="CONTEXTO_ORGAO_C_USUARIO_LOGIN" value="teste" />
    <const name="CONTEXTO_ORGAO_C_USUARIO_SENHA" value="teste" />        
    <const name="CONTEXTO_ORGAO_C_NOME_UNIDADE_SECUNDARIA" value="" />  
    <const name="CONTEXTO_ORGAO_C_SIGLA_UNIDADE_SECUNDARIA" value="" />
    <const name="CONTEXTO_ORGAO_C_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA" value="" />
    <const name="CONTEXTO_ORGAO_C_TIPO_PROCESSO" value="Arrecadação: Cobrança" />    
    <const name="CONTEXTO_ORGAO_C_TIPO_DOCUMENTO" value="Ofício" />
    <const name="CONTEXTO_ORGAO_C_TIPO_DOCUMENTO_NAO_MAPEADO" value="Nota" />           
    <const name="CONTEXTO_ORGAO_C_HIPOTESE_RESTRICAO" value="Documento Preparatório (Art. 7º, § 3º, da Lei nº 12.527/2011)" />    
    <const name="CONTEXTO_ORGAO_C_HIPOTESE_RESTRICAO_NAO_MAPEADO" value="Informação Pessoal (Art. 31 da Lei nº 12.527/2011)" />
    <const name="CONTEXTO_ORGAO_C_CARGO_ASSINATURA" value="Assessor(a)" />       
    <const name="CONTEXTO_ORGAO_C_HIPOTESE_RESTRICAO_PADRAO" value="Controle Interno (Art. 26, § 3º, da Lei nº 10.180/2001)" />
  </php>

  <testsuites>
    <testsuite name="funcional">
      <directory>tests</directory>
    </testsuite>
  </testsuites>
</phpunit>

```

### 5. Rodar os Testes Funcionais

na pasta raiz do módulo
1. suba o ambiente
	```
	make up
	```
2. instale o módulo
	```
	make install
	```
	*Antes de rodar o install certifique-se que o sistema já subiu*
	
	Aqui tem uma orientação importante. O Makefile vai tentar buildar o projeto de teste. Isso só vai funcionar se vc tiver o php instalado e com as extensões corretas. Mas não é obrigatório ter o php. Esse build é a última etapa da instalação. Caso deseje você pode ignorar esse erro e rode os seguintes comandos para substituir esse passo, sem a necessidade de instalar php no seu host:

	```
		docker exec -it org1-http bash
		cd /opt/sei/web/modulos/mod-sei-pen/
		./composer.phar install -d tests_sei4/funcional/
		./composer.phar install -d tests_sei4/unitario/
		exit
	```
	Uma vez executando os comandos acima será gerado uma pasta "vendor" dentro da pasta funcional. Não é necessário rodar novamente a não ser q a pasta seja excluída.


3. rode o teste
	```
	make test-functional
	```
	

	Nesta modalidade ele vai rodar todos os testes possíveis na sequência.
	A ordem é a que os arquivos são listados na pasta: tests_sei4/funcional/tests/ 
	Ver arquivos que terminem com *Test.php

Caso deseje rodar apenas 1 teste, defina o mesmo no arquivo Makefile alterando a variável:
```
teste=TramiteProcessoAnexadoTest
```
*caso o Makefile esteja com a var setada o make test-functional vai rodar apenas o teste indicado, no caso o TramiteProcessoAnexadoTest. Verifique também que colocamos apenas o nome do arquivo sem a extensão .php*

### 6. Rodar Rotina de Recebimento de Processos em Paralelo

O agendamento de recebimento de processos demora 2 minutos para ocorrer. Gerando espera desnecessária no teste. Para adiantar isso pode-se abrir um novo terminal; acesse a pasta do módulo e digite: 

```
make tramitar-pendencias
```
*roda o processo de recebimento 4x na sequencia para cada orgao.*

ou
```
make tramitar-pendencias-silent
```
*roda o processo de recebimento 300x para cada orgão*

ou

```
while true; do  make tramitar-pendencias; sleep 10; done
```
*roda de dez em dez segundos o recebimento de pendências, indefinidamente.*


### 7. Rodar vários testes paralelamente

Estamos experimentando o make test-functional-parallel e make test-parallel-otimizado que usando o paratest consegue rodar em paralelo os testes para melhorar a performance. Assim que tivermos maiores informações vamos atualizar aqui na documentação o seu uso.


### 8. Visualizar os Testes

Os testes rodam no conteiner selenium. 
O docker-compose do makefile já expõe a porta 5900 desse conteiner no host ou na sua VM.

Para visualizar o andamento dos testes (ver o robô operando o browser), basta conectar seu cliente VNC favorito a porta 5900 da sua VM ou host.

Por ex: caso o docker do projeto esteja rodando no seu host, basta abrir o VNC e mandar conectar no 127.0.0.1:5900
Caso esteja rodando uma vm basta mandar conectar no ip da vm e mesma coisa, porta 5900.

A senha para conectar via vnc é: 
```
secret
```
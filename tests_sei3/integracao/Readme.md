---------------------------------------------------
CONFIGURAÇÃO DO PROJETO DE TESTES FUNCIONAIS DO SEI
---------------------------------------------------

1 - Atualizar as Dependências do Projeto
Acessar o diretório do projeto sei-teste e atualizar as dependências do composer
>> composer install

2 - Executar o Servidor de Teste Selenium
PS: Em caso de erro "Connection Refused", verificar se a versão do chromedriver informada no parâmetro -Dwebdriver.chrome.driver é compatível com a versão do Chrome instalada

Linux:
>> java -jar -Dwebdriver.chrome.driver=lib/drivers/chromedriver-2.38 lib/selenium-server-standalone-3.11.0.jar

Windows:
>> java -jar -Dwebdriver.chrome.driver=lib/drivers/chromedriver-2.38.exe lib/selenium-server-standalone-3.11.0.jar


4 - Configurar pré-requisitos necessários para que o teste execute corretamente
Antes de executar os testes, a aplicação deverá ser acessada para configurar os seguintes parâmetros de teste que irão guiar diferentes cenários de teste do sistema:

4.1 - Configurar todos os parâmetros do arquivo phpunit.xml para guiar o teste de acordo com a atual configuração do banco de dados. Os principais são os seguintes:

 * PHPUNIT_TESTS_URL

 * CONTEXTO_ORGAO_A
 
 * CONTEXTO_ORGAO_A_URL
 
 * CONTEXTO_ORGAO_A_SIGLA_ORGAO
 
 * CONTEXTO_ORGAO_A_REP_ESTRUTURAS
 
 * CONTEXTO_ORGAO_A_SIGLA_UNIDADE
 
 * CONTEXTO_ORGAO_A_NOME_UNIDADE
 
 * CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA
 
 * CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA
 
 * CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA
 
 * CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA
 
 * CONTEXTO_ORGAO_A_USUARIO_LOGIN
 
 * CONTEXTO_ORGAO_A_USUARIO_SENHA
 
 * CONTEXTO_ORGAO_A_TIPO_PROCESSO
 
 * CONTEXTO_ORGAO_A_TIPO_DOCUMENTO
 
 * CONTEXTO_ORGAO_A_TIPO_DOCUMENTO_NAO_MAPEADO
 
 * CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO
 
 * CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO_NAO_MAPEADO
 
 * CONTEXTO_ORGAO_A_CARGO_ASSINATURA
 
 * CONTEXTO_ORGAO_A_HIPOTESE_RESTRICAO_PADRAO
 



3 - Executar o Teste Funcional Automatizado

Linux:
>> ./vendor/bin/phpunit 

Windows:
>> .\vendor\bin\phpunit.bat



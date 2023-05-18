# CONFIGURAÇÃO DO PROJETO DE TESTES FUNCIONAIS DO SEI


## 1. Instalação dos pré-requisitos

Todos os componentes abaixo precisam ser instalados antes de iniciar os testes automatizados.

### 1.1 Instalação das extensões do PHP

´´´bash
sudo apt install php-dom php-mbstring php-curl php-soap php-mysql

´´´ 

### 1.2 Instalação do gerenciador de pacotes Composer

´´´bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
´´´ 


## 2. Atualização das dependências do projeto

O mod-sei-pen utiliza o PHP Unit e outros utilitários de testes que possuem suas dependencias gerenciadas pelo Composer. Maiores informações sobre como instalar este gerenciados de pacotes para PHP podem ser encontradas em https://getcomposer.org/.
Acesse o diretório do projeto ```tests/``` e execute o comando abaixo para atualizar as depedências do projeto.

```bash
$ composer install
```

## 3. Execução do servidor de teste selenium

Para que os testes possam simular a interação com um navegador web, é utilizado a ferramenta Selenium (selenium-webdriver). Portanto, será necessário ativar o servidor do Selenium antes de iniciar, indicando qual o driver correto para o navegador a ser utilizado nos testes.

PS: Em caso de erro "Connection Refused", verificar se a versão do chromedriver informada no parâmetro -Dwebdriver.chrome.driver é compatível com a versão do Chrome instalada

Linux:
``` bash
java -jar -Dwebdriver.chrome.driver=lib/drivers/chromedriver-<VERSAO DO DRIVER> lib/selenium-server-standalone-3.11.0.jar
```

## 4. Configurar pré-requisitos necessários para que o teste execute corretamente
Antes de executar os testes, a aplicação deverá ser revisada para verificar se os parâmetros estão devidamente configurados para a cenário de teste que irá ser executado. A Configuração aplicada encontra-se definida no arquivo **phpunit.xml**.


### 5. Execução dos testes funcionais automatizados

```bash
$ ./vendor/bin/phpunit --testsuite funcional
``` 


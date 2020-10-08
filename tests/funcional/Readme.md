# CONFIGURAÇÃO DO PROJETO DE TESTES FUNCIONAIS DO SEI

Há duas alternativas para executar os testes:
- 1. Método 1 - instalando as ferramentas no ambiente local - ver seção 1 abaixo
- 2. Método 2 - utilizando conteineres docker - ver seção 2 abaixo

## 1. Tradicional

### 1.1. Instalação dos pré-requisitos

Todos os componentes abaixo precisam ser instalados antes de iniciar os testes automatizados.

#### 1.1.1 Instalação das extensões do PHP

´´´bash
sudo apt install php-dom php-mbstring php-curl php-soap php-mysql

´´´ 

#### 1.1.2 Instalação do gerenciador de pacotes Composer

´´´bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
´´´ 


### 1.2. Atualização das dependências do projeto

O mod-sei-pen utiliza o PHP Unit e outros utilitários de testes que possuem suas dependencias gerenciadas pelo Composer. Maiores informações sobre como instalar este gerenciados de pacotes para PHP podem ser encontradas em https://getcomposer.org/.
Acesse o diretório do projeto ```tests/``` e execute o comando abaixo para atualizar as depedências do projeto.

```bash
$ composer install
```

### 1.3. Execução do servidor de teste selenium

Para que os testes possam simular a interação com um navegador web, é utilizado a ferramenta Selenium (selenium-webdriver). Portanto, será necessário ativar o servidor do Selenium antes de iniciar, indicando qual o driver correto para o navegador a ser utilizado nos testes.

PS: Em caso de erro "Connection Refused", verificar se a versão do chromedriver informada no parâmetro -Dwebdriver.chrome.driver é compatível com a versão do Chrome instalada

Linux:
``` bash
java -jar -Dwebdriver.chrome.driver=lib/drivers/chromedriver-<VERSAO DO DRIVER> lib/selenium-server-standalone-3.11.0.jar
```

### 1.4. Configurar pré-requisitos necessários para que o teste execute corretamente
Antes de executar os testes, a aplicação deverá ser revisada para verificar se os parâmetros estão devidamente configurados para a cenário de teste que irá ser executado. A Configuração aplicada encontra-se definida no arquivo **phpunit.xml**.


### 1.5. Execução dos testes funcionais automatizados

```bash
$ ./vendor/bin/phpunit --testsuite funcional
``` 


## 2. Docker

### 2.1. Compilar os testes

Após o provisionamento do ambiente com o comando "make test-provision"

Compile os testes com o seguinte comando:

``` 
cd <pasta do projeto>/tests/funcional/

docker run --rm -it -v "${PWD}":/t -w /t  linhares/php72-cli-mysql-ora-sqls:1 composer --ansi install -o

```

* Isso vai gerar a pasta vendor - caso deseje apagá-la será necessário usar o comando sudo pois o conteiner usa um outro usuário para criá-la


### 3.1. Rodar os testes

``` 
cd <pasta do projeto>/tests/funcional/

docker run --rm -it --network funcional_mod-sei-pen-net -v "${PWD}":/t -v "${PWD}"/assets/arquivos/arquivo_pequeno_A.pdf:/tmp/arquivo_pequeno_A.pdf -v "${PWD}"/assets/arquivos/arquivo_pequeno_B.pdf:/tmp/arquivo_pequeno_B.pdf -v "${PWD}"/assets/arquivos/arquivo_pequeno_C.pdf:/tmp/arquivo_pequeno_C.pdf -w /t linhares/php72-cli-mysql-ora-sqls:1 sh -c 'php vendor/bin/phpunit -c phpunit.xml  --stop-on-failure --testsuite funcional'

```
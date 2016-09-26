# Módulo de Integração do SEI com o Barramento de Serviços do PEN

Procedimentos para colaborar com o desenvolvimento do módulo.

1. Baixe os códigos-fontes do SEI na versão compatível com o módulo
2. Crie o diretório sei/institucional, caso ainda não exista, para receber os códigos do módulo
3. No diretório sei/institucional faça o clone do projeto
4. Configure o módulo nos arquivos de configuração do SEI, seção SEI > Modulos
```php
    'Modulos' => array('PEN' => dirname(__FILE__).'/institucional/mod-sei-barramento')
```
5. Copie os arquivos de atualização do banco de dados do módulos para os devidos sistemas.
    * Copiar o arquivo "sei_atualizar_versao_modulo_pen.php" para a pasta sei
    * Copiar o arquivo "sip_atualizar_versao_modulo_pen.php" para a pasta sip

6. Execute a atualização do banco de dados do SEI e SIP
* Executar o script "sip_atualizar_versao_modulo_pen.php" para atualizar o banco de dados do SIP para o funcionamento do módulo:
```bash
    php sip_atualizar_versao_modulo_pen.php
```
* Executar o script "sei_atualizar_versao_modulo_pen.php" para inserção de dados no banco do SEI referente as funcionalidades desenvolvidas no módulo.
```bash
	php sei_atualizar_versao_modulo_pen.php
```

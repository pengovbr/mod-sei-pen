# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.0.0)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN. 

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

#### Issue #99 - Ajustar configurações do docker-compose,env e phpunit para SEI3

Ajustar arquivos de configuração de ambiente para funcionar no ambiente do SEI3.

#### Issue #98 - Ajuste no CSS da página de configuração do módulo PEN

A presente página se enconta com erros de CSS.

#### Issue #97 - Criar compatibilidade entre .env do SEI3 e SEI4

Criar arquivos de ambiente diferentes para cada versão do SEI.

#### Issue #96 - Ajustes no Makefile para nova estrutra de containers de teste isolado

Ajustar docker-compose para criar um novo container específico para rodar os testes no PHPUNIT.

#### Issue #95 - Ajustes para funcionamento do Xdebug no novo container de teste

Ajustar configurações do XDEBUG para funcionamento em um novo container de testes isolado.

#### Issue #94 - Criar container para rodar testes funcionais no Oracle e Sqlserver

Instalar bilbiotecas necessárias para execução dos testes em Oracle e Sqlserver

#### Issue #93 - Ajustar instalação do módulo no sqlserver que apresenta travamento

No script de instalação do módulo ocorre uma mensagem de warning que trava o processo de instalação.

#### Issue #92 - Ajustar rotinas do crontab que não estão executando

As rotinas presentes no crontab do SEI não estão executando na nova versão do SEI.

#### Issue #90 - Ajuste na funcionalidade de exportar em ZIP

Com a atualização de versão do SEI a funcionalidade de exportar PDF deixou de funcionar

#### Issue #89 - Ajuste no CSS nas páginas de adminsitração do PEN

As diversas páginas de adminsitração do PEN apresentam erros de CSS.

#### Issue #88 - Ajustes no ambiente docker para funcionar na nova versão SEI

É necessário alterar volumes, imagens e variáveis de ambiente para replicar o novo ambiente

#### Issue #87 - Otimização dos testes funcionais para reduzir tempo de execução

Os testes funcionais estão apresentando timeout com a atualização para o SEI4.

#### Issue #86 - Correção da funcionalidade de alteração de URL do órgão 

O módulo não está calculando corretamente o hash dos documentos após alteração de URL

#### Issue #85 - Correções dos testes funcionais automatizados

Os diversos testes automatizados estão com problemas devido as alterações de nomes e display do SEI4

#### Issue #84 - Ajuste no CSS dos ícones de envio e recibos do PEN

Os ícones antigos do módulo estão diferentes do novo estilo visual dos ícones.

#### Issue #83 - Ajuste no ícones de CRUD das páginas de administração

Os ícones nas páginas de configuração do SEI foram movidos de diretório.

#### Issue #82 - Ajuste no CSS de tramite de processo

A janela que mostra a barra de envio do módulo apresenta um CSS incorreto.




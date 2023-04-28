# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.15)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o Barramento de Serviços do PEN.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizada em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
    * 3.1.0 até 3.1.7, 
    * 4.0.0 até 4.0.6
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior


## Lista de Melhorias e Correções de Problemas

### Correção de incompatibilidades entre PHP 5.6 e 7.4 nas rotina de envio de processo

Correção de falhas relacionadas a incompatibilidades entre o PHP 5.6, utilizada utilizada pelo SEI 3.1, e o PHP 7.4, utilizado pelo SEI 4.0. O problema impedia a abertura de qualquer processo no SEI 3.1 caso o módulo estivesse ativado. 

### Correção de recebimento de anexados com mais de um componente digital com SEI 3.1

Correção de problema com recebimento de processos contendo documentos com anexos na utilização do módulo com o SEI 3.1. 
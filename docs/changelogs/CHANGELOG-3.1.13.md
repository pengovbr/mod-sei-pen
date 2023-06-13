# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.13)

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

### Issue #135 - Modificado regra de pre-validação de envio externo para conferir impedimentos de bloqueio

Módulo não pré-validava se processo era possível de bloqueio, impedindo que regras do SEI e regras extendidas de 
módulos pudessem ser corretamente validadas antes do envio externo.

### Issue #151 - Correção de formatação, estilos e layouts de páginas de configuração

Algumas páginas de configuração do módulo apresentavam problemas de formatação após atualização de suporte para SEI 4.0.

### Issue #150 - Remoção de validação de compatibilidade na inicialização do SEI

Removido a validação da correta configuração do módulo e sua compatibilidade
com de versões, o que impedia a correção inicialização do sistema. Modificado
para apenas notificar o administrador sobre as incompatibilidades através dos
logs do sistema.

### Correção de funcionamento da página de seleção de unidades para envio externo do processo

Corrigido erro na página de navegação por árvores para seleção de unidades administrativas para envio externo do processo, 
impedindo que os registros ficassem em carregamento por tempo indeterminado. Problema ocorria na versão 4.0 do SEI

### Adição de ícones para os menus de módulo

A partir do SEI 4.0, os menus laterais do sistema passaram a permitir a apresentação de ícones para representar 
as funcionalidades, inclusive aquelas relacionadas aos módulos


#### Compatibilidade com SEI 4.0.4 a 4.0.6

Verificada a compatibilidade do módulo com as versões do SEI 4.0.4, 4.0.5 e 4.0.6


## Outros ajustes

* Adição de documentação sobre preparação de ambiente de desenvolvimento do módulo
* Ajuste em template de abertura de novas issues no github


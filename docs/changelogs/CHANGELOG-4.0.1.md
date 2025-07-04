# NOTAS DE VERSÃO MOD-SEI-PEN (versão 4.0.1)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com a seguinte versão do **SEI**:
  * SEI 5.0.0
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.
* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### **CORREÇÕES DE PROBLEMAS**

#### Nesta versão, foram corrigidos os seguintes erros:

* **Erro no envio de processo quando o tipo 'Outros' é definido como padrão:** Ao inserir um novo tipo de documento em um processo e tentar tramitar, mesmo com o mapeamento de tipos de documentos para envio configurado, uma mensagem de erro é exibida e o trâmite é interrompido. [#811](https://github.com/pengovbr/mod-sei-pen/issues/811);

* **Erro no retorno de busca "Unidade não encontrada" utilizando caracteres especiais ou ID da unidade** Na tela "Mapeamento de Unidades", "Envio de Trâmite Externo" ou "Envio Externo de Processos do Bloco de Trâmite", ao pesquisar por uma unidade utilizando caracteres especiais ou o ID da unidade no Portal do Tramita GOV.BR, o campo de busca retorna a mensagem "Unidade não encontrada". [#810](https://github.com/pengovbr/mod-sei-pen/issues/810);

* **Melhoria de mensagem sobre falha de obtenção das unidades externas. Versão 4.0.1** Melhoria de mensagem sobre falha de obtenção das unidades externas. Versão 4.0.1 Atualização de mensagem na tela de mapeamento de unidades, atualizando de "Falha na obtenção de unidades externas" para "A unidade pesquisada não está vinculada a estrutura organizacional, [Nome da Estrutura], selecionada. Por favor, verifique se a unidade pertence a outra estrutura. [#852](https://github.com/pengovbr/mod-sei-pen/issues/852);

* **Chamado Nº 22777612 - Falha de envio do componente digital. (IBAMA) / Versão 4.0.1** Correção de erro 404 Not Found no endpoint tickets-de-enviode-componente. [#850](https://github.com/pengovbr/mod-sei-pen/issues/850);

* **Erro: Módulo do Tramita: Falha identificada na definição da ordem dos componentes digitais do documento** Correção de erro ao tentar reenviar um processo com documento externo removido apresenta o erro: Módulo do Tramita: Falha identificada na definição da ordem dos componentes digitais. [#836](https://github.com/pengovbr/mod-sei-pen/issues/836);



#### Instruções

1. Baixar a última versão do módulo de instalação do sistema (arquivo `mod-sei-pen-[VERSÃO].zip`) localizado na página de [Releases do projeto MOD-SEI-PEN](https://github.com/spbgovbr/mod-sei-pen/releases), seção **Assets**. _Somente usuários autorizados previamente pela Coordenação-Geral do Processo Eletrônico Nacional podem ter acesso às versões._

2. Fazer backup dos diretórios "sei", "sip" e "infra" do servidor web;

3. Descompactar o pacote de instalação `mod-sei-pen-[VERSÃO].zip`;

4. Copiar os diretórios descompactados "sei", "sip" para os servidores, sobrescrevendo os arquivos existentes;

5. Executar o script de instalação/atualização `sei_atualizar_versao_modulo_pen.php` do módulo para o SEI localizado no diretório `sei/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

6. Executar o script de instalação/atualização `sip_atualizar_versao_modulo_pen.php` do módulo para o SIP localizado no diretório `sip/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

7. Verificar a correta instalação e configuração do módulo

Para executar a verificação, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diretório de scripts do SEI ```<DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRETÓRIO RAIZ DE INSTALAÇÃO DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

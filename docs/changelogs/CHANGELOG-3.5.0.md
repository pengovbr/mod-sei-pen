# NOTAS DE VERS�O MOD-SEI-PEN (vers�o 3.5.0)

Este documento descreve as principais mudan�as aplicadas nesta vers�o do m�dulo de integra��o do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das vers�es s�o cumulativas, ou seja, cont�m todas as implementa��es realizadas em vers�es anteriores.

## Compatibilidade de vers�es
* O m�dulo � compat�vel com as seguintes vers�es do **SEI**:
    * 3.1.0 at� 3.1.7, 
    * 4.0.0 at� 4.0.12
    
Para maiores informa��es sobre os procedimentos de instala��o ou atualiza��o, acesse os seguintes documentos localizados no pacote de distribui��o mod-sei-pen-VERSAO.zip:
> Aten��o: � impreter�vel seguir rigorosamente o disposto no README.md do M�dulo para instala��o ou atualiza��o com sucesso.

* **INSTALACAO.md** - Procedimento de instala��o e configura��o do m�dulo
* **ATUALIZACAO.md** - Procedimento espec�ficos para atualiza��o de uma vers�o anterior

### Lista de melhorias e corre��es de problemas

Todas as atualiza��es podem incluir itens referentes � seguran�a, requisito em permanente monitoramento e evolu��o, motivo pelo qual a atualiza��o com a maior brevidade poss�vel � sempre recomendada.

#### Mapeamento de Tipos de Processos: Cadastro de Relacionamento entre �rg�os (#250)

Esta melhoria � parte do pacote referente � funcionalidade de Blocos de Migra��o.

### Atualiza��o de Vers�o

Para obter informa��es detalhadas sobre cada um dos passos de atualiza��o, vide arquivo **ATUALIZACAO.md**.

#### Instru��es

1. Baixar a �ltima vers�o do m�dulo de instala��o do sistema (arquivo `mod-sei-pen-[VERS�O].zip`) localizado na p�gina de [Releases do projeto MOD-SEI-PEN](https://github.com/spbgovbr/mod-sei-pen/releases), se��o **Assets**. _Somente usu�rios autorizados previamente pela Coordena��o-Geral do Processo Eletr�nico Nacional podem ter acesso �s vers�es._

2. Fazer backup dos diret�rios "sei", "sip" e "infra" do servidor web;

3. Descompactar o pacote de instala��o `mod-sei-pen-[VERS�O].zip`;

4. Copiar os diret�rios descompactados "sei", "sip" para os servidores, sobrescrevendo os arquivos existentes;

5. Executar o script de instala��o/atualiza��o `sei_atualizar_versao_modulo_pen.php` do m�dulo para o SEI localizado no diret�rio `sei/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRET�RIO RAIZ DE INSTALA��O DO SEI E SIP>/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
```

6. Executar o script de instala��o/atualiza��o `sip_atualizar_versao_modulo_pen.php` do m�dulo para o SIP localizado no diret�rio `sip/scripts/mod-pen/`

```bash
php -c /etc/php.ini <DIRET�RIO RAIZ DE INSTALA��O DO SEI E SIP>/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
```

7. Verificar a correta instala��o e configura��o do m�dulo

Para executar a verifica��o, execute o script ```verifica_instalacao_modulo_pen.php``` localizado no diret�rio de scripts do SEI ```<DIRET�RIO RAIZ DE INSTALA��O DO SEI E SIP>/sei/scripts/mod-pen/```.

```bash
$ php -c /etc/php.ini <DIRET�RIO RAIZ DE INSTALA��O DO SEI E SIP>/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
``` 

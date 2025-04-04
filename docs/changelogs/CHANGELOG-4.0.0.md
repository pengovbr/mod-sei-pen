# NOTAS DE VERSÃO MOD-SEI-PEN (versão 4.0.0)

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


* **Altera a mensagem quando o tipo de processo não existe no sistema de destino:** Altera a mensagem quando o tipo de processo não existe no sistema de destino  [#781](https://github.com/pengovbr/mod-sei-pen/issues/781);
 
 
#### **MELHORIAS**

#### As melhorias implementadas nesta versão incluem:

* **Compatibilidade com o SEI v.5.0.0:** Compatibilização do Tramita com o módulo SEI v. 5.0.0 [#764](https://github.com/pengovbr/mod-sei-pen/issues/764);

* **Correção do erro ao tentar enviar para o Tramita:** [41f4fa730317f5452c90a266d92cc5b6f57886df];
Os seguintes erros de validação de campos foram identificados: - processo.documentos[X].especie.nomeNoProdutor não pode ser vazio processo.documentos[X].especie.codigo deve ser um código de espécie válido

* **Correção dos acentos ao filtrar as unidades:** [028e598746f658752b84e0b48fa58d40fbee8f19]
Erro de encoding UTF8 que não exibia corretamente a acentuação nos filtros das unidades

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

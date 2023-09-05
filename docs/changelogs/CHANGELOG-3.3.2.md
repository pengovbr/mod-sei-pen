# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.3.2)

Este documento descreve as principais mudanças aplicadas nesta versão do módulo de integração do SEI com o TRAMITA.GOV.BR.

As melhorias entregues em cada uma das versões são cumulativas, ou seja, contêm todas as implementações realizadas em versões anteriores.

## Compatibilidade de versões
* O módulo é compatível com as seguintes versões do **SEI**:
    * 3.1.0 até 3.1.7, 
    * 4.0.0 até 4.0.12
    
Para maiores informações sobre os procedimentos de instalação ou atualização, acesse os seguintes documentos localizados no pacote de distribuição mod-sei-pen-VERSAO.zip:
> Atenção: É impreterível seguir rigorosamente o disposto no README.md do Módulo para instalação ou atualização com sucesso.

* **INSTALACAO.md** - Procedimento de instalação e configuração do módulo
* **ATUALIZACAO.md** - Procedimento específicos para atualização de uma versão anterior

### Lista de melhorias e correções de problemas

Todas as atualizações podem incluir itens referentes à segurança, requisito em permanente monitoramento e evolução, motivo pelo qual a atualização com a maior brevidade possível é sempre recomendada.

#### Erro ao tramitar com unidade com descrição maior que 100 caracteres 'is not a valid utf-8 string' (#294) 

Unidade adminstrativa com descrição de 100 caracteres dava erro caso tivesse algum caractere acentuado.

#### Atualização do ícone de Envio Externo (#296)

Novo ícone de envio externo.

#### Atualização do ícone de 'Consultar Recibos'. (#308)

Novo ícone de Consultar Recibos.

#### Criação de Ícone para indicativo de processo enviado por meio do Tramita.GOV.BR (#226)

Criado ícone Nna tela de controle de processos e do detalhes do processo para indicar que o processo já foi tramitado alguma vez pelo Tramita. 

#### Atualização do ícone do Cancelamento de Envio Externo (#297)

Novo ícone de Cancelamento de Envio Externo.

#### Módulo do Tramita não exibe todas as unidades disponíveis para envio de processo (#242)

Ao buscar na lista de unidades disponíveis para envio a partir de um determinado critério qualquer de busca (como um trecho de nome ou de sigla de órgão ou unidade), a droplist exibida com os resultados sugeridos apresenta apenas as 20 (vinte) primeiras ocorrências, deixando de fora - nos casos em que há mais de 20 unidades disponíveis que satisfaçam o critério - quaisquer unidades que não as 20 primeiras. Agora é listado todas as unidades.

#### Recusa por Hipótese Legal não encontrada. (#275)

Nova mensagem 'O Administrador do Sistema de Destino não definiu uma Hipótese de Restrição Padrão para o recebimento de trâmites por meio do Tramita.GOV.BR. Por esse motivo, o trâmite foi recusado' para a recusa com a antiga mensagem 'Hipótese legal não encotrada'.

#### Ícone de Cancelar Tramitação Externa Duplicado (#355)

Remoção de ícone duplicado da tramitação externa.

### Atualização de Versão

Para obter informações detalhadas sobre cada um dos passos de atualização, vide arquivo **ATUALIZACAO.md**.

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

# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.3.3)

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

#### Erro no Trâmite de Processo convertido de Documento Avulso (#239) 

Um documento avulso foi recebido pelo SEI e convertido em processo. O usuário incluiu novos documentos e tentou tramitar novamente.

#### Melhoria da Mensagem - Alteração de Unidade de Mapeamento. (#277)

Ao alterar um mapeamento já existente para alguma unidade mapeada anteriormente acontece um erro no momento de salvar( veja o vídeo abaixo): Já existe um registro com a 'Unidade RH' para o código 151860.

#### Melhoria de Mensagem: Transação não autorizada, pois o sistema não é responsável pela estrutura organizacional remetente. (#332)

A mensagem Transação não autorizada, pois o sistema não é responsável pela estrutura organizacional remetente não está clara e dificulta a análise pelo Gestor de Protocolo.

#### Melhoria da Mensagem - Unidade [Estrutura: XXXXXX] não configurada para receber processos externos no sistema de destino. (#337)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: Unidade [Estrutura: XXXX] não configurada para receber processos externos no sistema de destino.

#### Melhoria da Mensagem - Processo recusado devido a existência de documento em formato teste não permitido pelo sistema. (#338)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: Processo recusado devido a existência de documento em formato [Nome do Formato4] não permitido pelo sistema.

#### Melhoria da Mensagem - Já existe um processo utilizando o número de protocolo XXXXXX.XXXXXX/XXXXXX -XX. (#339)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: Já existe um processo utilizando o número de protocolo XXXXXX.XXXXXX/XXXXXX -XX.

#### Melhoria da Mensagem - O tamanho máximo permitido para arquivos XXXXX é XXXMb. (#340)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: O tamanho máximo permitido para arquivos XXXXXXX é XXXMb.

#### Melhoria da Mensagem - Documento do tipo XXXXX não está mapeado (#342)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: Documento do tipo XXXXX não está mapeado.

#### Melhoria da Mensagem - O tamanho máximo geral permitido para documentos externos é XXXX Mb. (#343)

Ao enviar um processo de um órgão para outro, o mesmo pode ser recusado pelo seguinte motivo: O tamanho máximo geral permitido para documentos externos é XXXX Mb.

#### Sistema não está aplicando os filtros de pesquisa da tela de mapeamento de unidades. (#347)

Foi verificado que na tela de mapeamento de unidades, o sistema não está obedecendo a pesquisa através dos campos 'Sigla' e 'Descrição'.

#### Recusa pelo motivo "Documento não foi recebido pela unidade atual." (#379)

Ao tentar devolver um processo onde foram retirados documentos originalmente criados por um determinado órgão o SEI recusa informando que: "Documento não foi recebido pela unidade atual.".

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

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

#### Mapeamento de Unidade - Inclusão do campo Unidade Tramita.GOV.BR (#238)

Adiciona texto com sigla e descrição da unidade mapeada do Tramita.GOV.BR para a unidade do SEI.

#### Erro no Trâmite de Processo convertido de Documento Avulso (#239) 

Um documento avulso foi recebido pelo SEI e convertido em processo. O usuário incluiu novos documentos e tentou tramitar novamente.

#### Melhoria da Mensagem - Alteração de Unidade de Mapeamento. (#277)

Nova mensagem 'A unidade [Nome da Unidade] do sistema já está mapeada a unidade [Nome da Unidade] do Portal de Administração'.

#### Melhoria de Mensagem: Transação não autorizada, pois o sistema não é responsável pela estrutura organizacional remetente. (#332)

Nova mensagem 'Por favor, observe o seguinte procedimento para realizar o mapeamento adequado: Acesse a funcionalidade Administração, em seguida selecione Processo Eletrônico Nacional e, por fim, proceda ao mapeamento utilizando somente as unidades pertinentes ao seu órgão/entidade na funcionalidade Mapeamento de Unidades. Certifique-se de seguir esse processo para garantir a correta execução do mapeamento'.

#### Melhoria da Mensagem - Unidade [Estrutura: XXXXXX] não configurada para receber processos externos no sistema de destino. (#337)

Nova mensagem 'A Unidade [Nome da Unidade cadastrada no Portal de Administração] não está configurada para receber processos/documentos avulsos por meio da plataforma. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Melhoria da Mensagem - Processo recusado devido a existência de documento em formato teste não permitido pelo sistema. (#338)

Nova mensagem 'O formato [Nome do Formato] não é permitido pelo sistema de destino. Lembre-se que cada órgão/ entidade tem autonomia na definição de quantos e quais formatos de arquivo são aceitos pelo seu sistema. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Melhoria da Mensagem - Já existe um processo utilizando o número de protocolo XXXXXX.XXXXXX/XXXXXX -XX. (#339)

Nova mensagem 'Um processo com o número de protocolo XXXXXX.XXXXXX/XXXXXX -XX já existe no sistema de destino. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Melhoria da Mensagem - O tamanho máximo permitido para arquivos XXXXX é XXXMb. (#340)

Nova mensagem 'O tamanho máximo permitido para arquivo XXXXX é XXXMb. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Melhoria da Mensagem - Documento do tipo XXXXX não está mapeado (#342)

Nova mensagem 'O Documento do tipo XXXXX não está mapeado para recebimento no sistema de destino. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Melhoria da Mensagem - O tamanho máximo geral permitido para documentos externos é XXXX Mb. (#343)

Nova mensagem 'O tamanho máximo geral permitido para documentos externos é XXXX Mb. OBS: A recusa é uma das três formas de conclusão de trâmite. Portanto, não é um erro'.

#### Sistema não está aplicando os filtros de pesquisa da tela de mapeamento de unidades. (#347)

Foi verificado que na tela de mapeamento de unidades, o sistema não está obedecendo a pesquisa através dos campos 'Sigla' e 'Descrição'.

#### Recusa pelo motivo "Documento não foi recebido pela unidade atual." (#379)

Ao tentar devolver um processo onde foram retirados documentos originalmente criados por um determinado órgão o SEI recusa informando que: "Documento não foi recebido pela unidade atual.".

#### Correção do phpcs e alertas de XSS

Correção dos alertas apontados pelo phpcs.

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

# NOTAS DE VERSÃO MOD-SEI-PEN (versão 3.1.14)

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

### Correção de erro ao receber processos contendo mais de um interessado com mesmo nome (#154)

Corrigido problema ao receber processos proveniente de outros sistemas em que é registrado um determinado interessado mais de uma vez nos metadados do processo. Nesta situação, o módulo estava rejeitando o processo informando que foram encontrados participantes duplicados.

### Correção de reenvio de processo contendo mais de um componente digital (#156)

Corrigido falha ao reenviar processos com documentos contendo mais de um componente digital. Quando o sistema recebe documento com esta característica, estes são consolidados em um único arquivo compactado e adicionados ao processo. O problema ocorria quando este mesmo processo era reenviado, em que o arquivo compactado era enviado no lugar dos componentes originais.
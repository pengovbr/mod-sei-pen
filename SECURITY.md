# Política de Segurança, Privacidade e Vulnerabilidades

## Versões com suporte

| Versão  | Suporte            |
| ------- | ------------------ |
| 4.0.x   | :white_check_mark: |
| < 4.0   | :x:                |

## 1. Objetivo

Esta política estabelece as diretrizes para identificação, comunicação, avaliação, tratamento e divulgação responsável de vulnerabilidades de segurança relacionadas ao **Módulo de Integração do SEI ao Tramita.GOV.BR (mod-sei-pen)**.

O projeto integra o Sistema Eletrônico de Informações (SEI) ao Tramita.GOV.BR, permitindo o trâmite de processos e documentos entre instituições integrantes do Processo Eletrônico Nacional (PEN).

Em razão da natureza do sistema, vulnerabilidades que afetem confidencialidade, integridade, autenticidade ou disponibilidade de processos, documentos, metadados, credenciais ou informações relacionadas ao trâmite devem ser tratadas com prioridade.

## 2. Escopo

Esta política abrange os componentes mantidos neste repositório, incluindo:

* código-fonte PHP;
* integrações com o SEI e SIP;
* integrações com o Tramita.GOV.BR;
* APIs e serviços utilizados pelo módulo;
* mecanismos de envio e recebimento de processos e documentos;
* funcionalidades de tramitação externa;
* mecanismos de autenticação, autorização e controle de acesso;
* scripts de instalação e atualização;
* configurações do módulo;
* dependências de software;
* testes automatizados relacionados à segurança;
* pacotes de distribuição e artefatos publicados pelo projeto.

A política não transfere para os mantenedores do projeto a responsabilidade pela segurança da infraestrutura, da instalação do SEI, da rede institucional ou dos demais sistemas operados pelas instituições usuárias.

## 3. Princípios de segurança

O projeto adota, dentro de seu escopo, os seguintes princípios:

* segurança desde a concepção (*Security by Design*);
* privacidade desde a concepção (*Privacy by Design*);
* menor privilégio;
* segregação de funções;
* defesa em profundidade;
* autenticação e autorização adequadas;
* minimização da exposição de informações;
* rastreabilidade de operações relevantes;
* gestão contínua de vulnerabilidades;
* atualização de dependências;
* divulgação responsável;
* proteção da cadeia de suprimentos de software.

## 4. Responsabilidades

### 4.1. Mantenedores do projeto

Compete aos mantenedores, dentro do escopo do projeto:

* receber e analisar relatos de vulnerabilidade;
* avaliar a severidade e o impacto potencial;
* identificar versões afetadas;
* desenvolver ou coordenar correções;
* disponibilizar correções e mitigações;
* avaliar impactos sobre integrações e componentes dependentes;
* atualizar dependências vulneráveis quando aplicável;
* comunicar vulnerabilidades relevantes de maneira coordenada;
* preservar informações necessárias à investigação.

### 4.2. Instituições usuárias

Cabe à instituição que instala e opera o módulo:

* manter o SEI, SIP, sistema operacional, banco de dados e demais componentes atualizados;
* proteger credenciais e certificados;
* configurar adequadamente autenticação e autorização;
* restringir acesso às interfaces administrativas;
* proteger os ambientes de produção;
* manter controles de rede;
* monitorar eventos de segurança;
* manter registros e trilhas de auditoria;
* realizar backups;
* estabelecer procedimentos de resposta a incidentes;
* avaliar os riscos específicos de sua implantação;
* cumprir suas obrigações legais e regulatórias relacionadas ao tratamento de dados.

## 5. Proteção de processos e documentos

O módulo pode participar do processamento e da transmissão de processos, documentos e respectivos metadados entre instituições.

Consequentemente, vulnerabilidades que possam permitir:

* acesso indevido a processos;
* acesso indevido a documentos;
* alteração não autorizada de documentos;
* alteração indevida de metadados;
* inclusão ou remoção indevida de documentos;
* envio de documentos para instituição incorreta;
* falsificação ou manipulação de informações de tramitação;
* bypass de autorização;
* exposição de conteúdo restrito;

devem ser consideradas vulnerabilidades de segurança e reportadas de forma privada.

## 6. Autenticidade e integridade

As informações relacionadas ao trâmite de processos e documentos devem ser protegidas contra alterações não autorizadas.

São particularmente relevantes vulnerabilidades que permitam:

* falsificar a origem de uma solicitação;
* modificar informações durante o transporte;
* alterar destinatários;
* alterar identificadores de processos ou documentos;
* modificar estados de tramitação;
* reproduzir solicitações legítimas de maneira indevida;
* realizar ataques de repetição (*replay*);
* contornar mecanismos de validação;
* provocar divergência entre sistemas de origem e destino.

## 7. Controle de acesso

Vulnerabilidades relacionadas a controle de acesso devem ser reportadas quando permitirem acesso ou execução de operações além das permissões previstas.

Exemplos incluem:

* escalada horizontal de privilégios;
* escalada vertical de privilégios;
* acesso a processos de outras unidades;
* acesso a documentos sem autorização;
* execução de funções administrativas por usuários não autorizados;
* bypass de perfis ou permissões;
* manipulação direta de parâmetros para acessar recursos de terceiros;
* falhas de validação de autorização no servidor.

A existência de uma interface ou funcionalidade acessível tecnicamente não deve ser interpretada como autorização para acesso aos respectivos dados ou operações.

## 8. Integrações e APIs

Vulnerabilidades em interfaces de integração com o Tramita.GOV.BR ou outros componentes devem ser reportadas quando puderem comprometer:

* autenticação;
* autorização;
* confidencialidade;
* integridade;
* disponibilidade;
* validação de mensagens;
* identificação das instituições;
* identificação dos processos;
* identificação dos documentos;
* controle de origem e destino.

Também devem ser reportadas falhas de validação que permitam manipulação de parâmetros, mensagens ou objetos utilizados na integração.

## 9. Proteção de dados pessoais

O módulo pode ser utilizado em ambientes que tratam dados pessoais e, potencialmente, dados pessoais sensíveis. Os processos considerados privados não são passíveis de envio.

A **Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD)** determina a adoção de medidas técnicas e administrativas aptas a proteger dados pessoais contra acessos não autorizados e situações acidentais ou ilícitas.

O projeto deve ser utilizado de maneira compatível com as políticas de segurança e privacidade da instituição responsável pelo ambiente.

A utilização do módulo não determina, por si só, quem é controlador, operador ou outro agente de tratamento. Essa definição deve ser realizada de acordo com o tratamento efetivamente realizado pela instituição.

## 10. Dados pessoais em relatórios de vulnerabilidade

**Não envie dados pessoais reais ao projeto para demonstrar uma vulnerabilidade.**

Também não devem ser enviados:

* processos reais;
* documentos reais;
* credenciais;
* tokens;
* certificados privados;
* chaves privadas;
* informações classificadas;
* dados de cidadãos;
* dados de servidores;
* dados de partes processuais;
* informações institucionais sigilosas.

Para provas de conceito, devem ser utilizados dados sintéticos ou fictícios.

Caso uma evidência exija obrigatoriamente dados reais, deverá minimizar a quantidade de informações compartilhadas e utilizar canal privado.

## 11. Incidentes envolvendo dados pessoais

Uma vulnerabilidade não significa necessariamente que ocorreu um incidente de segurança.

Entretanto, caso uma vulnerabilidade do módulo tenha sido explorada em ambiente de produção e possa ter resultado em acesso, alteração, perda, destruição ou exposição de dados pessoais, a instituição responsável pelo ambiente deverá tratar o evento segundo seus procedimentos de resposta a incidentes.

A organização deverá realizar sua própria avaliação quanto:

* à natureza dos dados afetados;
* às categorias e quantidade de titulares;
* às categorias e quantidade de dados;
* às circunstâncias do incidente;
* às consequências potenciais;
* à existência de risco ou dano relevante;
* às medidas de contenção e mitigação adotadas.

Quando aplicável, deverão ser observadas as obrigações de comunicação e demais requisitos estabelecidos pela LGPD e pela regulamentação da ANPD. A ANPD orienta que o controlador avalie o incidente e, quando houver risco ou dano relevante aos titulares, observe o procedimento de comunicação previsto na regulamentação aplicável.

## 12. Comunicação de vulnerabilidades

**Não abra uma Issue pública, Pull Request ou Discussion para reportar uma vulnerabilidade de segurança.**

O reporte deverá ser realizado por meio do mecanismo privado de reporte de vulnerabilidades disponibilizado pelo GitHub para este repositório.

### Informações recomendadas

Sempre que possível, o reporte deve conter:

* descrição da vulnerabilidade;
* componente afetado;
* versão ou commit afetado;
* pré-condições necessárias;
* passos para reprodução;
* impacto;
* evidências técnicas;
* prova de conceito;
* sugestão de correção ou mitigação;
* indicação de exploração ativa ou conhecida;
* indicação de eventual impacto sobre dados pessoais.

## 13. Severidade

A severidade poderá ser avaliada utilizando metodologia reconhecida, como **CVSS**, complementada por análise contextual.

Além da severidade técnica, deverá ser considerado o impacto potencial sobre:

* processos administrativos;
* documentos oficiais;
* dados pessoais;
* dados pessoais sensíveis;
* autenticidade documental;
* integridade de informações de tramitação;
* confidencialidade institucional;
* disponibilidade do serviço;
* outras instituições integrantes do ecossistema PEN.

Uma vulnerabilidade tecnicamente classificada como moderada poderá receber tratamento prioritário quando seu contexto de exploração representar risco elevado para informações ou serviços públicos.

## 14. Vulnerabilidades críticas

São consideradas especialmente críticas as vulnerabilidades que permitam, direta ou indiretamente:

* execução arbitrária de código;
* comprometimento do servidor SEI;
* comprometimento da infraestrutura de integração;
* acesso não autorizado a processos;
* acesso não autorizado a documentos;
* alteração de documentos ou metadados;
* falsificação de tramitações;
* envio de documentos para destinatário indevido;
* bypass de autenticação;
* bypass de autorização;
* escalada de privilégios;
* exfiltração de informações;
* exposição massiva de dados pessoais;
* comprometimento de credenciais ou certificados;
* comprometimento da comunicação entre instituições.

## 15. Gestão de dependências

O projeto utiliza componentes de terceiros e deve considerar vulnerabilidades existentes nesses componentes.

Quando aplicável, os mantenedores poderão:

* monitorar vulnerabilidades conhecidas;
* atualizar dependências;
* corrigir versões vulneráveis;
* remover dependências desnecessárias;
* avaliar impacto de atualizações;
* registrar versões utilizadas;
* acompanhar vulnerabilidades relevantes para o ecossistema PHP/SEI.

As instituições usuárias também devem manter atualizados os componentes que não são controlados diretamente por este projeto.

## 16. Pacotes de distribuição e releases

O projeto disponibiliza pacotes de instalação e atualização por meio das Releases do GitHub. O repositório informa que esses pacotes são utilizados para instalação e atualização do módulo.

Recomenda-se que as instituições:

* utilizem releases oficiais;
* validem a origem dos pacotes;
* mantenham registro da versão instalada;
* mantenham procedimentos de rollback;
* realizem backup antes de atualizações;
* testem atualizações em ambiente não produtivo;
* apliquem atualizações de segurança prioritariamente.

## 17. Atualizações de segurança

As versões do projeto podem conter correções de segurança juntamente com correções funcionais.

As notas de versão atualmente destacam que as atualizações podem incluir itens relacionados à segurança e recomendam a atualização com a maior brevidade possível.

Quando uma vulnerabilidade relevante for corrigida, os mantenedores poderão publicar:

* nova versão;
* correção;
* orientação de mitigação;
* GitHub Security Advisory;
* identificação de versões afetadas;
* instruções de atualização.

## 18. Divulgação coordenada

O projeto adota o princípio de divulgação coordenada.

Detalhes suficientes para exploração não deverão ser divulgados publicamente antes que os mantenedores tenham oportunidade razoável de:

1. confirmar a vulnerabilidade;
2. avaliar o impacto;
3. identificar versões afetadas;
4. desenvolver uma correção ou mitigação;
5. disponibilizar a correção;
6. comunicar usuários afetados, quando necessário.

Quando aplicável, poderá ser atribuído um identificador CVE e/ou publicado um GitHub Security Advisory.

## 19. Tratamento de incidentes

A descoberta de uma vulnerabilidade no código não substitui o processo de resposta a incidentes da instituição que utiliza o módulo.

Quando houver indícios de exploração em produção, recomenda-se que a instituição:

1. contenha o incidente;
2. preserve evidências;
3. avalie o escopo;
4. identifique sistemas e dados potencialmente afetados;
5. avalie credenciais que possam ter sido comprometidas;
6. implemente medidas de erradicação;
7. aplique correções;
8. monitore o ambiente;
9. documente as ações realizadas;
10. avalie as obrigações legais e regulatórias aplicáveis.

Informações sobre um incidente envolvendo ambiente de terceiros não devem ser publicadas em Issues ou Pull Requests públicas.

## 20. Preservação de evidências

Durante investigações, poderão ser preservados, conforme necessário:

* logs de aplicação;
* logs de autenticação;
* logs de integração;
* registros de acesso;
* identificadores de requisições;
* timestamps;
* versões instaladas;
* hashes de arquivos;
* configurações;
* registros de firewall;
* evidências de rede;
* informações de processos e documentos afetados.

Evidências contendo dados pessoais ou informações sigilosas devem ser protegidas contra acesso não autorizado e compartilhadas somente quando necessário.

## 21. Segredos e credenciais

Nenhum segredo de produção deve ser armazenado no repositório.

Isso inclui:

* senhas;
* tokens;
* certificados privados;
* chaves privadas;
* credenciais de APIs;
* credenciais de bancos de dados;
* credenciais de serviços de integração;
* arquivos de configuração contendo segredos.

Caso uma credencial seja publicada acidentalmente, ela deverá ser considerada comprometida e revogada ou substituída imediatamente.

A exclusão posterior do arquivo não é suficiente para considerar o segredo seguro, pois ele pode permanecer no histórico do Git ou em cópias do repositório.

## 22. Ambiente de testes

Vulnerabilidades devem ser reproduzidas preferencialmente em ambientes isolados e não produtivos.

É proibido utilizar sistemas de outras instituições, ambientes de produção ou dados reais sem autorização expressa.

Pesquisadores devem utilizar a menor quantidade de dados, privilégios e interação necessária para demonstrar a vulnerabilidade.

## 23. Testes de segurança responsáveis

São incentivados testes não destrutivos destinados a identificar vulnerabilidades.

Não são autorizados, sem permissão específica do responsável pelo ambiente:

* indisponibilização deliberada de serviços;
* destruição ou alteração de processos;
* alteração de documentos;
* exfiltração de dados;
* acesso a dados pessoais além do estritamente necessário;
* obtenção de credenciais de terceiros;
* persistência não autorizada;
* movimentação lateral;
* ataques de negação de serviço;
* testes contra ambientes produtivos de terceiros.

## 24. Safe Harbor

Pesquisadores que atuarem de boa-fé, respeitando esta política e evitando acesso desnecessário a dados ou sistemas de terceiros, são incentivados a reportar suas descobertas.

A equipe do projeto buscará tratar relatos responsáveis de maneira colaborativa e sem exposição desnecessária.

Esta disposição não constitui autorização para realizar testes em sistemas que não pertençam ao cliente ou para os quais ele não possua autorização.

## 25. Responsabilidade institucional

O `mod-sei-pen` é um componente de software integrante de uma arquitetura maior.

A segurança efetiva de uma implantação depende também de:

* versão do SEI;
* versão do SIP;
* servidor de aplicação;
* PHP;
* banco de dados;
* sistema operacional;
* infraestrutura de rede;
* certificados;
* mecanismos de autenticação;
* controles de acesso;
* configuração do Tramita.GOV.BR;
* políticas institucionais;
* procedimentos operacionais;
* monitoramento;
* gestão de vulnerabilidades.

Portanto, a existência de uma versão corrigida do módulo não elimina a necessidade de avaliação de segurança de toda a implantação.

## 26. Conformidade

Esta política deve ser observada em conjunto com a legislação, regulamentação e políticas institucionais aplicáveis, especialmente:

* Lei nº 13.709/2018 — Lei Geral de Proteção de Dados Pessoais (LGPD);
* regulamentações da Autoridade Nacional de Proteção de Dados (ANPD);
* normas e políticas de segurança da informação aplicáveis à Administração Pública;
* requisitos institucionais de segurança e privacidade;
* requisitos aplicáveis ao Processo Eletrônico Nacional;
* demais normas legais e regulamentares pertinentes.

Esta política não constitui parecer jurídico e não substitui a avaliação jurídica, de segurança da informação, privacidade ou gestão de riscos da instituição responsável pela implantação.

## 27. Atualização da política

Esta política poderá ser revisada sempre que houver alteração relevante:

* na arquitetura do módulo;
* nas integrações;
* nos mecanismos de autenticação;
* no processo de distribuição;
* nos canais de reporte;
* na legislação ou regulamentação;
* nos processos de segurança;
* no ecossistema do Tramita.GOV.BR.

As alterações relevantes deverão ser registradas no histórico do repositório.

## 28. Agradecimentos

Agradecemos aos pesquisadores, profissionais de segurança, instituições usuárias e colaboradores que contribuírem de forma responsável para melhorar a segurança do `mod-sei-pen` e do ecossistema do Processo Eletrônico Nacional.


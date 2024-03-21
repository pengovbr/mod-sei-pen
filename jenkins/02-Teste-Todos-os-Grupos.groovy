/*

Usuario jenkins precisa ter permissao de sudo
Jenkins minimo em 2.332
usa o pipeline utility plugin

chama o job 01 de forma serializada
crie as credenciais instanciamysql, instanciasqlserver e oracle de acordo com o
modelo na pasta jenkins/assets

*/

pipeline {
    agent {
        node{
            label "master"
        }
    }

    options {
        disableConcurrentBuilds()
    }

    parameters {

        string(
            name: 'versaoModulo',
            defaultValue:"master",
            description: "Branch/Tag do git onde encontra-se o Modulo")
        string(
            name: 'branchGitSpe',
            defaultValue:"4.0.11",
            description: "Branch/Tag do git onde encontra-se o Sistema")
        choice(
            name: 'sistema',
            choices: "sei4\nsei41\nsei3\nsuper",
            description: 'Qual o Sistema de Processo Eletr�nico ser� utilizado nos testes?' )
        choice(
            name: 'database',
            choices: "mysql\noracle\nsqlserver\npostgresql",
            description: 'Qual o banco de dados' )


    }

    stages {



        stage("Preparar execucao"){

            steps{


                script{

                    if ( env.BUILD_NUMBER == '1' ){
                        currentBuild.result = 'ABORTED'
                        warning('Informe os valores de parametro iniciais. Caso eles n tenham aparecido fa�a login novamente')
                    }

                    BRANCHGITSPE = params.branchGitSpe
                    SISTEMA = params.sistema
                    VERSAOMODULO = params.versaoModulo
                    DATABASE = params.database

                    withCredentials([file(credentialsId: "instanciamysql", variable: 'INSTANCIA_MYSQL'),
                                     file(credentialsId: "instanciasqlserver", variable: 'INSTANCIA_SQLSERVER'),
                                     file(credentialsId: "instanciaoracle", variable: 'INSTANCIA_ORACLE'),
                                     file(credentialsId: "instanciaorgao7_8.txt", variable: 'INSTANCIA_ORGAO7_8'),
                                     file(credentialsId: "instanciaorgao9_10.txt", variable: 'INSTANCIA_ORGAO9_10'),
                                     file(credentialsId: "instanciaorgao11_12.txt", variable: 'INSTANCIA_ORGAO11_12'),
                                     file(credentialsId: "instanciaorgao13_14.txt", variable: 'INSTANCIA_ORGAO13_14'),
                                     file(credentialsId: "instanciaorgao15_16.txt", variable: 'INSTANCIA_ORGAO15_16'),
                                    ]) {
                        sh "cp \$INSTANCIA_MYSQL instanciamysql.props"
                        sh "cp \$INSTANCIA_SQLSERVER instanciasqlserver.props"
                        sh "cp \$INSTANCIA_ORACLE instanciaoracle.props"
                        sh "cp \$INSTANCIA_ORGAO7_8 instanciaorgao7_8.props"
                        sh "cp \$INSTANCIA_ORGAO9_10 instanciaorgao9_10.props"
                        sh "cp \$INSTANCIA_ORGAO11_12 instanciaorgao11_12.props"
                        sh "cp \$INSTANCIA_ORGAO13_14 instanciaorgao13_14.props"
                        sh "cp \$INSTANCIA_ORGAO15_16 instanciaorgao15_16.props"
                    }

                    s = "${SISTEMA}: ${BRANCHGITSPE} / ${DATABASE} / Mod: ${VERSAOMODULO}"

                    //buildName "${SISTEMA}: ${GITBRANCH} / ${DATABASE} / Mod: ${VERSAOMODULO}"
                    buildDescription s

                }

            }

        }

        stage("Executar nas Bases"){

            parallel {
                stage("Grupo de Testes 1"){
                    steps{
                    script{


                    def props = readProperties  file: 'instanciamysql.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                                string(name: 'database', value: database),
                                string(name: 'org1CertSecret', value: org1CertSecret),
                                string(name: 'passCertOrg1', value: passCertOrg1),
                                string(name: 'org2CertSecret', value: org2CertSecret),
                                string(name: 'passCertOrg2', value: passCertOrg2),
                                string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                                string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                                string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                                string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                                string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                                string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                                string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                                string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                                string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                                string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                                string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                                string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                                string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                                string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                                string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                                string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                                string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                                string(name: 'branchGitSpe', value: BRANCHGITSPE),
                                string(name: 'sistema', value: SISTEMA),
                                string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_without_receiving+execute_parallel_with_two_group1"),


                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 2"){

                    steps{
                        script{


                    def props = readProperties  file: 'instanciasqlserver.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_parallel_group1+execute_parallel_group3"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 3"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaoracle.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_parallel_group2+execute_alone_group1"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 4"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaorgao7_8.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_alone_group2"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 5"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaorgao9_10.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_alone_group3"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 6"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaorgao11_12.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_alone_group4"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 7"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaorgao13_14.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_alone_group5"),
                            ], wait: true
                    }}

                }

                stage("Grupo de Testes 8"){

                    steps{
                        script{

                    def props = readProperties  file: 'instanciaorgao15_16.props'
                    database = DATABASE
                    org1CertSecret = props['org1CertSecret']
                    passCertOrg1 = props['passCertOrg1']
                    org2CertSecret = props['org2CertSecret']
                    passCertOrg2 = props['passCertOrg2']
                    CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
                    CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
                    CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
                    CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
                    CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
                    CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
                    CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA = props['CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA']
                    CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
                    CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']

                    build job: '01-Teste-Unico-ou-Grupo.groovy',
                        parameters:
                            [
                            string(name: 'database', value: database),
                            string(name: 'org1CertSecret', value: org1CertSecret),
                            string(name: 'passCertOrg1', value: passCertOrg1),
                            string(name: 'org2CertSecret', value: org2CertSecret),
                            string(name: 'passCertOrg2', value: passCertOrg2),
                            string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_A_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
                            string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA', value: CONTEXTO_ORGAO_B_SIGLA_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA', value: CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA),
                            string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NUMERO_SEI', value: CONTEXTO_ORGAO_B_NUMERO_SEI),
                            string(name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_B_REP_ESTRUTURAS),
                            string(name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA', value: CONTEXTO_ORGAO_B_ID_ESTRUTURA),
                            string(name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA),
                            string(name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE', value: CONTEXTO_ORGAO_B_NOME_UNIDADE),
                            string(name: 'branchGitSpe', value: BRANCHGITSPE),
                            string(name: 'sistema', value: SISTEMA),
                            string(name: 'versaoModulo', value: VERSAOMODULO),
                                string(name: 'grupos_executar', value: "execute_alone_group6"),
                            ], wait: true
                    }}

                }



            }

        }



    }

}

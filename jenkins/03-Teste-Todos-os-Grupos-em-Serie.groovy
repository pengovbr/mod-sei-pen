/*

Usuario jenkins precisa ter permissao de sudo
Jenkins minimo em 2.332

chama o job 02 de forma serializada

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
            name: 'versoes',
            defaultValue:"SPE=4.0.11,NOME_SPE=sei4,VERSAO_MODULO=master,BASE=mysql;SPE=4.0.12.15,NOME_SPE=sei4,VERSAO_MODULO=master,BASE=mysql;",
            description: "Lista de versoes do SPE e modulo no formato como exemplo acima, separados por ;")


    }

    stages {



        stage("Preparar execucao"){

            steps{

                script{

                    if ( env.BUILD_NUMBER == '1' ){
                        currentBuild.result = 'ABORTED'
                        warning('Informe os valores de parametro iniciais. Caso eles n tenham aparecido fa�a login novamente')
                    }

                    QTDTENTATIVAS=0
                    VERSOES_STRING = params.versoes
                    arrGeneral = VERSOES_STRING.split(';')

                    buildDescription "Versoes: ${VERSOES_STRING}"

                }

            }

        }

        stage("Executar nas Bases"){
            steps {
                script {

                    def paramValue
                    def spe_branch
                    def spe_controle_versao
                    def modulo_versao
                    def database

                    for (int i = 0; i < arrGeneral.length; i++) {
                        paramValue = arrGeneral[i].split(',')
                        spe_branch = paramValue[0].split('=')[1]
                        spe_nome = paramValue[1].split('=')[1]
                        modulo_versao = paramValue[2].split('=')[1]
                        database = paramValue[3].split('=')[1]

                        stage("Montando Ambiente Rodando Testes ${paramValue[0]} / ${paramValue[1]} / ${paramValue[2]}" ) {

                            warnError('Erro no build!'){

                                retry(QTDTENTATIVAS){


                                    build job: '02-Teste-Todos-os-Grupos.groovy',
                                        parameters:
                                            [
                                                string(name: 'branchGitSpe', value: spe_branch),
                                                string(name: 'sistema', value: spe_nome),
                                                string(name: 'versaoModulo', value: modulo_versao),
                                                string(name: 'database', value: database),
                                            ], wait: true
                                }

                            }

                        }


                    }

                }
            }

        }
    }

}

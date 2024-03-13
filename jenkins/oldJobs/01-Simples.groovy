/*

Usuario jenkins precisa ter permissao de sudo
Jenkins minimo em 2.332
criar secrets:
- credentialGitSpe
- org1CertSecret - secret file no jenkins com o cert all em formato pem para o orgao 1
- org2CertSecret - secret file no jenkins com o cert all em formato pem para o orgao 2
Obrigatorio que ele seja cadastrado no jenkins como um projeto apontando para um repo git
Nao subir direto o script no jenkins pois ele precisa do checkout inicial do repo
Nao rode ao mesmo tempo duas instancias da execucao no mesmo agente jenkins,
pois o projeto mod-sei-pen n suporta isso

Setar os agentes com a label MOD-SEI-PEN e colocar a qtd de alocadores apenas em 1
(para negar mais de um mesmo job ao mesmo tempo no mesmo agente, caso contrario um anula o outro)
O job 02 vai paralelizar esse job aqui portanto cd instancia deve executar em agentes separadas
e cada instancia deve usar orgas diferentes apontando para o barramento
*/

pipeline {
    agent {
        node{
            label "MOD-SEI-PEN"
        }
    }

    parameters {
		string(
		    name: 'versaoModulo',
		    defaultValue:"master",
		    description: "branch/versao do modulo a executar os testes")
        choice(
            name: 'database',
            choices: "mysql\noracle\nsqlserver",
            description: 'Qual o banco de dados' )
    	string(
    	      name: 'urlGitSpe',
    	      defaultValue:"github.com:supergovbr/super.git",
    	      description: "Url do git onde encontra-se o Sistema de Processo Eletrônico a instalar o modulo")
        string(
            name: 'credentialGitSpe',
            defaultValue:"gitcredsuper",
            description: "Jenkins Credencial do git onde encontra-se o Spe")
	    string(
	        name: 'branchGitSpe',
	        defaultValue:"4.0.3",
	        description: "Branch/Tag do git onde encontra-se o Spe")
		string(
		    name: 'folderSpe',
		    defaultValue:"/home/jenkins/spe",
		    description: "Pasta onde vai clonar o SPE")
        choice(
            name: 'sistema',
            choices: "sei4\nsei3\nsuper",
            description: 'Qual o Sistema de Processo Eletrônico será utilizado nos testes?' )
		string(
		    name: 'folderModulo',
		    defaultValue:"/home/jenkins/modulomodseipen",
		    description: "Pasta onde vai copiar o modulo depois de clonado e rodar o make a partir dela. Necessario para n travar exec pois o compose ainda usa root")
		booleanParam(
			defaultValue: false,
			name: 'bolFolderModuloDelete',
			description: 'Deleta a pasta do módulo anterior. Limpa o cache do phpunit vendor e os arquivos temporarios. Aumenta o tempo de execucao')
		string(
		    name: 'org1CertSecret',
		    defaultValue:"credModSeiPenOrg1Cert",
		    description: "Certificado de conexao ao tramita do orgao 1")
		string(
		    name: 'passCertOrg1',
		    defaultValue:"VLnYTwTSXdvU83sS",
		    description: "Password do certificado de conexao ao tramita do orgao 1")
		string(
		    name: 'org2CertSecret',
		    defaultValue:"credModSeiPenOrg2Cert",
		    description: "Certificado de conexao ao tramita do orgao 2")
		string(
            name: 'passCertOrg2',
            defaultValue:"LUisD2wEtpDc6cIj",
            description: "Password do certificado de conexao ao tramita do orgao 2")
		string(
            name: 'testParallel',
            defaultValue:"3",
            description: "Quantos processos simultaneos")
		string(
            name: 'testRetryCount',
            defaultValue:"5",
            description: "Quantas vezes deve repetir o teste caso o mesmo falhe")
		string(
		    name: 'CONTEXTO_ORGAO_A_NUMERO_SEI',
		    defaultValue:"951")
		string(
		    name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS',
		    defaultValue:"1")
		string(
		    name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS',
		    defaultValue:"Poder Executivo Federal")
		string(
		    name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA',
		    defaultValue:"307")
		string(
		    name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA',
		    defaultValue:"STF / PJ")
		string(
		    name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE',
		    defaultValue:"Supremo Tribunal Federal")
		string(
		    name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA',
		    defaultValue:"318")
		string(
		    name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA',
		    defaultValue:"Edital")
		string(
		    name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA',
		    defaultValue:"ED")
		string(
		    name: 'CONTEXTO_ORGAO_B_NUMERO_SEI',
		    defaultValue:"159")
		string(
		    name: 'CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS',
		    defaultValue:"1")
		string(
		    name: 'CONTEXTO_ORGAO_B_REP_ESTRUTURAS',
		    defaultValue:"Poder Executivo Federal")
		string(
		    name: 'CONTEXTO_ORGAO_B_ID_ESTRUTURA',
		    defaultValue:"79116")
		string(
		    name: 'CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA',
		    defaultValue:"PRODASEN")
		string(
		    name: 'CONTEXTO_ORGAO_B_NOME_UNIDADE',
		    defaultValue:"Prodasen")
		text(
		    name: 'testes',
		    defaultValue:"",
			description: "Passe aqui os testes para rodar em uma nova suite. Caso em branco vai rodar a suite funcionais. Passe em cada linha no seguinte formato: tests/RecebimentoRecusaJustificativaGrandeTest.php\ntests/TramiteProcessoComDevolucaoTest.php")

    }

    stages {

        stage('Inicializar'){

            steps {

                script{
                    DATABASE = params.database
					GITURL = params.urlGitSpe
					GITCRED = params.credentialGitSpe
					GITBRANCH = params.branchGitSpe
                    
                    VERSAOMODULO = params.versaoModulo

					FOLDERSPE = params.folderSpe
					SISTEMA = params.sistema
					FOLDERMODULO = params.folderModulo
					BOLFOLDERMODULODEL = params.bolFolderModuloDelete
					ORG1_CERT = params.org1CertSecret
					ORG1_CERT_PASS= params.passCertOrg1
					ORG2_CERT = params.org2CertSecret
                    ORG2_CERT_PASS= params.passCertOrg2
					
					TESTE_PARALLEL = params.testParallel
					TEST_RETRY_COUNT = params.testRetryCount

					CONTEXTO_ORGAO_A_NUMERO_SEI = params.CONTEXTO_ORGAO_A_NUMERO_SEI
					CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = params.CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS
					CONTEXTO_ORGAO_A_REP_ESTRUTURAS = params.CONTEXTO_ORGAO_A_REP_ESTRUTURAS
					CONTEXTO_ORGAO_A_ID_ESTRUTURA = params.CONTEXTO_ORGAO_A_ID_ESTRUTURA
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = params.CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA
					CONTEXTO_ORGAO_A_NOME_UNIDADE = params.CONTEXTO_ORGAO_A_NOME_UNIDADE
					CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = params.CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA
					CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = params.CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = params.CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA
					CONTEXTO_ORGAO_B_NUMERO_SEI = params.CONTEXTO_ORGAO_B_NUMERO_SEI
					CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = params.CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS
					CONTEXTO_ORGAO_B_REP_ESTRUTURAS = params.CONTEXTO_ORGAO_B_REP_ESTRUTURAS
					CONTEXTO_ORGAO_B_ID_ESTRUTURA = params.CONTEXTO_ORGAO_B_ID_ESTRUTURA
					CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = params.CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA
					CONTEXTO_ORGAO_B_NOME_UNIDADE = params.CONTEXTO_ORGAO_B_NOME_UNIDADE

					FOLDER_FUNCIONAIS = "tests_${SISTEMA}/funcional"

					TESTES = ""
					if(params.testes){
						testesarray = params.testes.split("\n")
						for (i=0; i< testesarray.length; i++){
						    TESTES += "<file>" + testesarray[i] + "</file>"
						}
					}

                    if ( env.BUILD_NUMBER == '1' ){
                        currentBuild.result = 'ABORTED'
                        warning('Informe os valores de parametro iniciais. Caso eles n tenham aparecido faça login novamente')
                    }
				}

				sh """
				ifconfig || true
				if [ -f ${FOLDERMODULO}/Makefile ]; then
				    make destroy || true
				fi

				mkdir -p ${FOLDERMODULO}
			    sudo chown -R jenkins ${FOLDERMODULO} || true
				sudo chmod +w -R ${FOLDERMODULO} || true

				if [ "${BOLFOLDERMODULODEL}" = "true" ]; then
				    rm -rf ${FOLDERMODULO}
					mkdir -p ${FOLDERMODULO}
				fi
				"""

                dir("${FOLDERSPE}"){

                    sh """
                    sudo rm -rf ${FOLDERSPE}/* || true
                    
                    git config --global http.sslVerify false
                    """

                    git branch: 'main',
                        credentialsId: GITCRED,
                        url: GITURL

                    sh """
                    git checkout ${GITBRANCH}
                    ls -l
                    
    				if [ -f ${FOLDERSPE}/src/sei/web/SEI.php ]; then
    				    rm -rf sei sip infra
                        mv src/sei src/sip src/infra .
    				fi 
                    """

                }

            }
        }
        
        stage('Checkout Modulo'){

            steps {

                sh """
                git config --global http.sslVerify false
                """

                git branch: 'master',
                    //credentialsId: GITCRED,
                    url: "https://github.com/supergovbr/mod-sei-pen"

                sh """
                git checkout ${VERSAOMODULO}
                ls -l
                """

				sh """
				ifconfig || true
				if [ -f ${FOLDERMODULO}/Makefile ]; then
				    make destroy || true
				fi

				mkdir -p ${FOLDERMODULO}
			    sudo chown -R jenkins ${FOLDERMODULO} || true
				sudo chmod +w -R ${FOLDERMODULO} || true

				if [ "${BOLFOLDERMODULODEL}" = "true" ]; then
				    rm -rf ${FOLDERMODULO}
					mkdir -p ${FOLDERMODULO}
				fi

				\\cp -R * ${FOLDERMODULO}

				"""

				dir("${FOLDERMODULO}"){

					withCredentials([file(credentialsId: "${ORG1_CERT}", variable: 'ORG1CERT'),
					                 file(credentialsId: "${ORG2_CERT}", variable: 'ORG2CERT')]) {
					    sh "cp \$ORG1CERT ${FOLDER_FUNCIONAIS}/assets/config/certificado_org1.pem"
					    sh "cp \$ORG2CERT ${FOLDER_FUNCIONAIS}/assets/config/certificado_org2.pem"
					}

	                sh script: """
	                make destroy || true

					sudo chmod +r ${FOLDER_FUNCIONAIS}/assets/config/certificado_org1.pem
					sudo chmod +r ${FOLDER_FUNCIONAIS}/assets/config/certificado_org2.pem
					sudo rm -rf ${FOLDERSPE}/sei/config/mod-pen
					sudo rm -rf ${FOLDERSPE}/sei/scripts/mod-pen
					sudo rm -rf ${FOLDERSPE}/sei/web/modulos/pen
					sudo rm -rf ${FOLDERSPE}/sip/config/mod-pen
					sudo rm -rf ${FOLDERSPE}/sei/scripts/mod-pen
					sudo rm -rf ${FOLDERSPE}/sei/config/ConfiguracaoSEI.php*
					sudo rm -rf ${FOLDERSPE}/sip/config/ConfiguracaoSip.php*
					
	                """, label: "Destroi ambiente e Remove Antigos"
					
				}

            }
        }

        stage('Subir Sistema - Instalar Modulo'){

            steps {
				retry(3){
				dir("${FOLDERMODULO}"){
	                sh script: """
					make destroy || true
					sed -i "s|sistema=.*|sistema=${SISTEMA}|g" Makefile
					sed -i "s|PARALLEL_TEST_NODES =.*|PARALLEL_TEST_NODES = ${TESTE_PARALLEL}|g" Makefile
					rm -rf ${FOLDER_FUNCIONAIS}/.env
					\\cp ${FOLDER_FUNCIONAIS}/env_${DATABASE} ${FOLDER_FUNCIONAIS}/.env
					sed -i "s|SEI_PATH=.*|SEI_PATH=${FOLDERSPE}|g" ${FOLDER_FUNCIONAIS}/.env
					sed -i "s|ORG1_CERTIFICADO_SENHA=.*|ORG1_CERTIFICADO_SENHA=$ORG1_CERT_PASS|g" ${FOLDER_FUNCIONAIS}/.env
					sed -i "s|ORG2_CERTIFICADO_SENHA=.*|ORG2_CERTIFICADO_SENHA=$ORG2_CERT_PASS|g" ${FOLDER_FUNCIONAIS}/.env

                    \\cp tests_sei4/funcional/phpunit.xml ${FOLDER_FUNCIONAIS}/phpunit.xml || true

					sed -i "s|.*PEN_WAIT_TIMEOUT\\".*|<const name=\\"PEN_WAIT_TIMEOUT\\" value=\\"40000\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES\\".*|<const name=\\"PEN_WAIT_TIMEOUT_ARQUIVOS_GRANDES\\" value=\\"180000\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE\\".*|<const name=\\"PEN_WAIT_TIMEOUT_PROCESSAMENTO_EM_LOTE\\" value=\\"180000\\"/>|g" ${FOLDER_FUNCIONAIS}/phpunit.xml

					sed -i "s|.*CONTEXTO_ORGAO_A_NUMERO_SEI\\".*|<const name=\\"CONTEXTO_ORGAO_A_NUMERO_SEI\\" value=\\"${CONTEXTO_ORGAO_A_NUMERO_SEI}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS\\".*|<const name=\\"CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS\\" value=\\"${CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_SIGLA_UNIDADE\\".*|<const name=\\"CONTEXTO_ORGAO_A_SIGLA_UNIDADE\\" value=\\"TESTE\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_REP_ESTRUTURAS\\".*|<const name=\\"CONTEXTO_ORGAO_A_REP_ESTRUTURAS\\" value=\\"${CONTEXTO_ORGAO_A_REP_ESTRUTURAS}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_ID_ESTRUTURA\\".*|<const name=\\"CONTEXTO_ORGAO_A_ID_ESTRUTURA\\" value=\\"${CONTEXTO_ORGAO_A_ID_ESTRUTURA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA\\".*|<const name=\\"CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA\\" value=\\"${CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_NOME_UNIDADE\\".*|<const name=\\"CONTEXTO_ORGAO_A_NOME_UNIDADE\\" value=\\"${CONTEXTO_ORGAO_A_NOME_UNIDADE}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml

					sed -i "s|.*CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA\\".*|<const name=\\"CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA\\" value=\\"TESTE_1_1\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA\\".*|<const name=\\"CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA\\" value=\\"${CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA\\".*|<const name=\\"CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA\\" value=\\"${CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA\\".*|<const name=\\"CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA\\" value=\\"${CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml

					sed -i "s|.*CONTEXTO_ORGAO_B_NUMERO_SEI\\".*|<const name=\\"CONTEXTO_ORGAO_B_NUMERO_SEI\\" value=\\"${CONTEXTO_ORGAO_B_NUMERO_SEI}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS\\".*|<const name=\\"CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS\\" value=\\"${CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_REP_ESTRUTURAS\\".*|<const name=\\"CONTEXTO_ORGAO_B_REP_ESTRUTURAS\\" value=\\"${CONTEXTO_ORGAO_B_REP_ESTRUTURAS}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_SIGLA_UNIDADE\\".*|<const name=\\"CONTEXTO_ORGAO_B_SIGLA_UNIDADE\\" value=\\"TESTE\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_ID_ESTRUTURA\\".*|<const name=\\"CONTEXTO_ORGAO_B_ID_ESTRUTURA\\" value=\\"${CONTEXTO_ORGAO_B_ID_ESTRUTURA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA\\".*|<const name=\\"CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA\\" value=\\"${CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
					sed -i "s|.*CONTEXTO_ORGAO_B_NOME_UNIDADE\\".*|<const name=\\"CONTEXTO_ORGAO_B_NOME_UNIDADE\\" value=\\"${CONTEXTO_ORGAO_B_NOME_UNIDADE}\\" />|g" ${FOLDER_FUNCIONAIS}/phpunit.xml
                    
					cp ${FOLDER_FUNCIONAIS}/phpunit.xml phpunitoriginal.xml
					
					
					make destroy
					make up
					make install

					pwd
	                """, label: "Configura sobe ambiente e instala modulo"
					
					script{
					if(TESTES){
						sh """
						cp phpunitoriginal.xml phpunitsubstituir.xml
						sed -i "s|<!-- novasuiteaqui -->|<testsuite name=\\"rodarnovamente\\">${TESTES}</testsuite>|g" phpunitsubstituir.xml
						cp phpunitsubstituir.xml ${FOLDER_FUNCIONAIS}/phpunit.xml

						sed -i "s|TEST_SUIT = funcional.*|TEST_SUIT = rodarnovamente|g" Makefile
						"""
					}
				    }
				}
			    }

            }
        }
		
        
		stage('Testes Unitarios'){
		
			steps{
			    dir("${FOLDERMODULO}"){
	                sh script: """				
					
					make test-unit
				
	                """, label: "Roda as Suites de Testes Unitarios"
			    }
			}
		
		}


        stage('Rodar Testes Funcionais'){


			parallel {

                stage('Testes Funcionais') {
                    steps {
						dir("${FOLDERMODULO}"){

							script{

								sh "rm -rf resultado_todos.txt;"

								GRUPOS = ["excluir", "rodarseparado1", "rodarseparado2", "rodarseparado4"]

								if (TESTES) {
									GRUPOS = [""]

								}
							    for (G in GRUPOS){

								    if (G == ""){ SUITE = "TEST_SUIT=rodarnovamente" } else { SUITE = "TEST_SUIT=funcional" }

								    TEST_GROUP_EXCLUIR = ""
									TEST_GROUP_INCLUIR = ""

									if(G=="excluir"){
										//SUITE="funcional2"
									    TEST_GROUP_EXCLUIR = 'TEST_GROUP_EXCLUIR="--exclude-group rodarseparado"'
										TEST_GROUP_INCLUIR = ""
									}
									if(G != "excluir" && G){
									    TEST_GROUP_EXCLUIR = ""
										TEST_GROUP_INCLUIR = """TEST_GROUP_INCLUIR="--group ${G}" """
									}

					                sh script: """
									
					                

									EXECUTAR_TESTES="true"
									ERRCOUNT=0
									SUITE_ATUAL=${SUITE}

									while [ "\$EXECUTAR_TESTES" = "true" ]; do

										EXECUTAR_TESTES="false"
										rm -rf resultado.txt

										set +e
										echo "Executando Testes Funcionais..."
										RESULTMAKE=\$?
										make \$SUITE_ATUAL ${TEST_GROUP_EXCLUIR} ${TEST_GROUP_INCLUIR} test-functional-parallel > resultado.txt
										RESULTMAKE=\$?
										set -e

										cat resultado.txt >> resultado_todos.txt

										grep -o -E "[0-9]\\) .*::" resultado.txt | sed "s|::||g" | cut -d\\  -f2 | uniq > rodarnovamente.txt

										s=""
										for t in \$(cat rodarnovamente.txt); do

										    s="\$s<file>tests/\$t.php</file>"

										done

										if [ ! "\$s" = "" ]; then

											ERRCOUNT=\$((ERRCOUNT+1))
											if [ \$ERRCOUNT -le ${TEST_RETRY_COUNT} ]; then
												EXECUTAR_TESTES="true"

												cp phpunitoriginal.xml phpunitsubstituir.xml
												sed -i "s|<!-- novasuiteaqui -->|<testsuite name=\\"rodarnovamente\\">\$s</testsuite>|g" phpunitsubstituir.xml
												cp phpunitsubstituir.xml ${FOLDER_FUNCIONAIS}/phpunit.xml

												SUITE_ATUAL="TEST_SUIT=rodarnovamente"

											fi
										else
											rm -rf rodarnovamente.txt
										fi


									done

							        if [ -f "rodarnovamente.txt" ]; then
									    exit 1

									fi;

							        if [ ! "\$RESULTMAKE" = "0" ]; then
									    #vamos tentar achar erro
										if [ "\$s" = "" ]; then
										    set +e
											grep "OK" resultado.txt
											RESULTMAKE=\$?
											set -e
											if [ ! "\$RESULTMAKE" = "0" ]; then
											    exit 1
											fi

										else
										    exit 1
										fi

									fi;


					                """, label: "Roda as Suites de Testes Funcionais"



								}

							}


						}


                    }
					post {

						failure {
						    dir("${FOLDERMODULO}"){
							sh script: """
							cat rodarnovamente.txt;
							""", label: "Testes que falharam"

							sh script: """
                            cat resultado.txt;
							""", label: "Stack de Erros do PHPUNIT"
						    }
						}

                        always {
							dir("${FOLDERMODULO}"){
                            sh """
							sleep 30;
							touch testesfinalizados.ok
							cat resultado_todos.txt
							"""

						    }
                        }
	                }

                }
                stage('Recebimento de Processos') {
                    steps {
						dir("${FOLDERMODULO}"){
							sh script: """
							while [ ! -f testesfinalizados.ok ]
							do
							  sleep 10
							  make tramitar-pendencias-simples
							done
							""", label: "Tramitar Pendencias"
						}
                    }
					post {
                        always {
							dir("${FOLDERMODULO}"){
                                sh "rm testesfinalizados.ok"
						    }
                        }
	                }
                }

			}

        }

    }
    post {
        always {
			dir("${FOLDERMODULO}"){
            sh script: """
		    rm -rf testesfinalizados.ok
            #sudo chown -R root:jenkins ${FOLDER_FUNCIONAIS}/assets/cron.d
			make destroy || true
			#sudo chown -R root:jenkins ${FOLDER_FUNCIONAIS}/assets/cron.d
            """, label: "Destroi Ambiente"
		    }
        }
    }
}

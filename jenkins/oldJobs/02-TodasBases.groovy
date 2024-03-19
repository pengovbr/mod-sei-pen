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
            choices: "sei4\nsei3\nsuper",
            description: 'Qual o Sistema de Processo Eletrônico será utilizado nos testes?' )
    	

    }

    stages {
		
		
        
		stage("Preparar execucao"){
			
			steps{
				
				script{
					
                    if ( env.BUILD_NUMBER == '1' ){
                        currentBuild.result = 'ABORTED'
                        warning('Informe os valores de parametro iniciais. Caso eles n tenham aparecido faça login novamente')
                    }
				    
					BRANCHGITSPE = params.branchGitSpe
					SISTEMA = params.sistema
                    VERSAOMODULO = params.versaoModulo
					
				    withCredentials([file(credentialsId: "instanciamysql", variable: 'INSTANCIA_MYSQL'),
					                 file(credentialsId: "instanciasqlserver", variable: 'INSTANCIA_SQLSERVER'),
									 file(credentialsId: "instanciaoracle", variable: 'INSTANCIA_ORACLE'),]) {
				        sh "cp \$INSTANCIA_MYSQL instanciamysql.props"
						sh "cp \$INSTANCIA_SQLSERVER instanciasqlserver.props"
						sh "cp \$INSTANCIA_ORACLE instanciaoracle.props"
				    }
				
				}
			
			}
		
		}
		
		stage("Executar nas Bases"){
		    
			parallel {
			    stage("Mysql"){
					steps{
					script{	
				
					
					def props = readProperties  file: 'instanciamysql.props'
					database = props['database']
					org1CertSecret = props['org1CertSecret']
					passCertOrg1 = props['passCertOrg1']
					org2CertSecret = props['org2CertSecret']
					passCertOrg2 = props['passCertOrg2']
					CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
					CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
					CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
					CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
					CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']
				   
					build job: '01-Simples.groovy',
                        parameters:
                            [
							    string(name: 'database', value: database),
                                string(name: 'org1CertSecret', value: org1CertSecret),
								string(name: 'passCertOrg1', value: passCertOrg1),
								string(name: 'org2CertSecret', value: org2CertSecret),
								string(name: 'passCertOrg2', value: passCertOrg2),
								string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
								string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
								string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
								string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
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
                            ], wait: true
					}}
				
				}
				
				stage("SqlServer"){
					
					steps{
						script{
				
			
					def props = readProperties  file: 'instanciasqlserver.props'
					database = props['database']
					org1CertSecret = props['org1CertSecret']
					passCertOrg1 = props['passCertOrg1']
					org2CertSecret = props['org2CertSecret']
					passCertOrg2 = props['passCertOrg2']
					CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
					CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
					CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
					CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
					CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']
				    
					build job: '01-Simples.groovy',
                        parameters:
                            [
							    string(name: 'database', value: database),
                                string(name: 'org1CertSecret', value: org1CertSecret),
								string(name: 'passCertOrg1', value: passCertOrg1),
								string(name: 'org2CertSecret', value: org2CertSecret),
								string(name: 'passCertOrg2', value: passCertOrg2),
								string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
								string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
								string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
								string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
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
                            ], wait: true
					}}
				
				}
				
				stage("Oracle"){
					
					steps{
						script{
					
					def props = readProperties  file: 'instanciaoracle.props'
					database = props['database']
					org1CertSecret = props['org1CertSecret']
					passCertOrg1 = props['passCertOrg1']
					org2CertSecret = props['org2CertSecret']
					passCertOrg2 = props['passCertOrg2']
					CONTEXTO_ORGAO_A_NUMERO_SEI = props['CONTEXTO_ORGAO_A_NUMERO_SEI']
					CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_A_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE = props['CONTEXTO_ORGAO_A_NOME_UNIDADE']
					CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA = props['CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA']
					CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA = props['CONTEXTO_ORGAO_A_NOME_UNIDADE_SECUNDARIA']
					CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA = props['CONTEXTO_ORGAO_A_SIGLA_UNIDADE_SECUNDARIA_HIERARQUIA']
					CONTEXTO_ORGAO_B_NUMERO_SEI = props['CONTEXTO_ORGAO_B_NUMERO_SEI']
					CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_ID_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_REP_ESTRUTURAS = props['CONTEXTO_ORGAO_B_REP_ESTRUTURAS']
					CONTEXTO_ORGAO_B_ID_ESTRUTURA = props['CONTEXTO_ORGAO_B_ID_ESTRUTURA']
					CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA = props['CONTEXTO_ORGAO_B_SIGLA_UNIDADE_HIERARQUIA']
					CONTEXTO_ORGAO_B_NOME_UNIDADE = props['CONTEXTO_ORGAO_B_NOME_UNIDADE']
				
					build job: '01-Simples.groovy',
                        parameters:
                            [
							    string(name: 'database', value: database),
                                string(name: 'org1CertSecret', value: org1CertSecret),
								string(name: 'passCertOrg1', value: passCertOrg1),
								string(name: 'org2CertSecret', value: org2CertSecret),
								string(name: 'passCertOrg2', value: passCertOrg2),
								string(name: 'CONTEXTO_ORGAO_A_NUMERO_SEI', value: CONTEXTO_ORGAO_A_NUMERO_SEI),
								string(name: 'CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_ID_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_REP_ESTRUTURAS', value: CONTEXTO_ORGAO_A_REP_ESTRUTURAS),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA),
								string(name: 'CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA', value: CONTEXTO_ORGAO_A_SIGLA_UNIDADE_HIERARQUIA),
								string(name: 'CONTEXTO_ORGAO_A_NOME_UNIDADE', value: CONTEXTO_ORGAO_A_NOME_UNIDADE),
								string(name: 'CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA', value: CONTEXTO_ORGAO_A_ID_ESTRUTURA_SECUNDARIA),
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
                            ], wait: true
					}}
				
				}
			}
			
		}   
	   
	  

    }
  
}

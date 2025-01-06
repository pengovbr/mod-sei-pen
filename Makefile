.PHONY: .env help clean dist all install destroy up update down test test-functional test-functional-parallel test-unit bash_org1 bash_org2 verify-config

# Parâmetros de execução do comando MAKE
# Opções possíveis para spe (sistema de proc eletronico): sei3, sei4, sei41, super
sistema=super
base=mysql
teste=

ifeq (, $(shell groups |grep docker))
 CMD_DOCKER_SUDO=sudo
else
 CMD_DOCKER_SUDO=
endif

ifeq (, $(shell which docker-compose))
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker compose
else
 CMD_DOCKER_COMPOSE=$(CMD_DOCKER_SUDO) docker-compose
endif

MODULO_NOME = pen
MODULO_PASTAS_CONFIG = mod-$(MODULO_NOME)
VERSAO_MODULO := $(shell grep 'define."VERSAO_MODULO_PEN"' src/PENIntegracao.php | cut -d'"' -f4)
SEI_SCRIPTS_DIR = dist/sei/scripts/mod-pen
SEI_CONFIG_DIR = dist/sei/config/mod-pen
SEI_BIN_DIR = dist/sei/bin/mod-pen
SEI_MODULO_DIR = dist/sei/web/modulos/pen
SIP_SCRIPTS_DIR = dist/sip/scripts/mod-pen
PEN_MODULO_COMPACTADO = mod-sei-tramitagovbr-$(VERSAO_MODULO).zip
PEN_TEST_FUNC = tests_$(sistema)/funcional
PEN_TEST_UNIT = tests_$(sistema)/unitario
PARALLEL_TEST_NODES = 5

PEN_TST_FNC_EVD = tests_$(sistema)/funcional/evidencias
PEN_TST_FNC_EVD_TFE = $(PEN_TST_FNC_EVD)/teste_funcional_especifico
NOME_ARQ_EVDNC_TFE = evidencia-teste_funcional_especifico
PEN_TST_FNC_EVD_TFC = $(PEN_TST_FNC_EVD)/teste_funcional_completo
NOME_ARQ_EVDNC_TFC = evidencia-teste_funcional_completo
PEN_TST_FNC_TESTS_FS = $(PEN_TEST_FUNC)/tests_funcional_falsos_positivos_temp
PEN_TST_FNC_EVD_TFP= $(PEN_TST_FNC_EVD)/teste_funcional_falsos_positivos
NOME_ARQ_EVDNC_TFP = evidencia-teste_funcional_falsos_positivos

-include $(PEN_TEST_FUNC)/.env

CMD_INSTALACAO_SEI = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php atualizar_versao_sei.php
CMD_INSTALACAO_SIP = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_versao_sip.php
CMD_INSTALACAO_RECURSOS_SEI = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_recursos_sei.php
CMD_INSTALACAO_SEI_MODULO = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php sei_atualizar_versao_modulo_pen.php
CMD_INSTALACAO_SIP_MODULO = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php sip_atualizar_versao_modulo_pen.php

CMD_COMPOSE_UNIT = $(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_UNIT)/docker-compose.yml --env-file $(PEN_TEST_UNIT)/.env
CMD_COMPOSE_FUNC = $(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env
CMD_COMPOSE_FUNC_EXEC = $(CMD_COMPOSE_FUNC) exec -T
CMD_CURL_LOGIN_ORG1 = $(CMD_COMPOSE_FUNC_EXEC) org1-http bash -c 'curl -s -L $${HOST_URL:-$$SEI_HOST_URL}/sei | grep -q "input.*txtUsuario.*"'
CMD_CURL_LOGIN_ORG2 = $(CMD_COMPOSE_FUNC_EXEC) org2-http bash -c 'curl -s -L $${HOST_URL:-$$SEI_HOST_URL}/sei | grep -q "input.*txtUsuario.*"'
FILE_VENDOR_FUNCIONAL = $(PEN_TEST_FUNC)/vendor/bin/phpunit
FILE_VENDOR_UNITARIO = $(PEN_TEST_UNIT)/vendor/bin/phpunit

all: help

check-isalive: ## Target de apoio. Acessa os Sistemas e verifica se estao respondendo a tela de login
	@echo ""
	@echo "Testando se a pagina de login responde para o org1..."
	@for i in `seq 1 15`; do \
	    echo "Tentativa $$i/15";  \
		if $(CMD_CURL_LOGIN_ORG1); then \
				echo 'Página de login encontrada!' ; \
				break ; \
		fi; \
		sleep 5; \
	done; \
	if ! $(CMD_CURL_LOGIN_ORG1); then echo 'Pagina de login do org1 nao encontrada. Verifique...'; exit 1 ; fi;
	@echo "Testando se a pagina de login responde para o org2..."
	@for i in `seq 1 15`; do \
	    echo "Tentativa $$i/15";  \
		if $(CMD_CURL_LOGIN_ORG2); then \
				echo 'Página de login encontrada!' ; \
				break ; \
		fi; \
		sleep 5; \
	done; \
	if ! $(CMD_CURL_LOGIN_ORG2); then echo 'Pagina de login do org2 nao encontrada. Verifique...'; exit 1 ; fi;


install-phpunit-vendor: ## instala os pacotes composer referentes aos testes via phpunit
	$(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_FUNC)/docker-compose.yml run --rm -w /tests php-test-functional bash -c './composer.phar install'
	$(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_FUNC)/docker-compose.yml run --rm -w /tests php-test-unit bash -c './composer.phar install'


$(FILE_VENDOR_FUNCIONAL): ## target de apoio verifica se o build do phpunit foi feito e executa apenas caso n exista
	make install-phpunit-vendor


$(FILE_VENDOR_UNITARIO): ## target de apoio verifica se o build do phpunit foi feito e executa apenas caso n exista
	make install-phpunit-vendor

dist: 
	# ATENÇÃO: AO ADICIONAR UM NOVO ARQUIVO DE DEPLOY, VERIFICAR O MESMO EM VerificadorInstalacaoRN::verificarPosicionamentoScriptsConectado
	@mkdir -p $(SEI_SCRIPTS_DIR)
	@mkdir -p $(SEI_CONFIG_DIR)
	@mkdir -p $(SEI_BIN_DIR)
	@mkdir -p $(SEI_MODULO_DIR)
	@mkdir -p $(SIP_SCRIPTS_DIR)
	@cp -R src/* $(SEI_MODULO_DIR)/
	@cp docs/INSTALL.md dist/INSTALACAO.md
	@cp docs/UPGRADE.md dist/ATUALIZACAO.md
	@cp docs/changelogs/CHANGELOG-$(VERSAO_MODULO).md dist/NOTAS_VERSAO.md
	@mv $(SEI_MODULO_DIR)/scripts/sei_atualizar_versao_modulo_pen.php $(SEI_SCRIPTS_DIR)/
	@mv $(SEI_MODULO_DIR)/scripts/sip_atualizar_versao_modulo_pen.php $(SIP_SCRIPTS_DIR)/
	@mv $(SEI_MODULO_DIR)/scripts/verifica_instalacao_modulo_pen.php $(SEI_SCRIPTS_DIR)/
	@mv $(SEI_MODULO_DIR)/scripts/MonitoramentoEnvioTarefasPEN.php $(SEI_SCRIPTS_DIR)/
	@mv $(SEI_MODULO_DIR)/scripts/MonitoramentoRecebimentoTarefasPEN.php $(SEI_SCRIPTS_DIR)/
	@mv $(SEI_MODULO_DIR)/scripts/ProcessamentoTarefasPEN.php $(SEI_SCRIPTS_DIR)/	
	@mv $(SEI_MODULO_DIR)/config/ConfiguracaoModPEN.exemplo.php $(SEI_CONFIG_DIR)/
	@mv $(SEI_MODULO_DIR)/config/supervisor.exemplo.ini $(SEI_CONFIG_DIR)/
	@mv $(SEI_MODULO_DIR)/bin/verificar-reboot-fila.sh $(SEI_BIN_DIR)/
	@mv $(SEI_MODULO_DIR)/bin/verificar-reboot-fila-sem-supervisor.sh $(SEI_BIN_DIR)/
	@mv $(SEI_MODULO_DIR)/bin/verificar-pendencias-represadas.py $(SEI_BIN_DIR)/
	@rm -rf $(SEI_MODULO_DIR)/config
	@rm -rf $(SEI_MODULO_DIR)/scripts
	@rm -rf $(SEI_MODULO_DIR)/bin
	@cd dist/ && zip -r $(PEN_MODULO_COMPACTADO) INSTALACAO.md ATUALIZACAO.md NOTAS_VERSAO.md sei/ sip/	
	@rm -rf dist/sei dist/sip dist/INSTALACAO.md dist/ATUALIZACAO.md
	@echo "Construção do pacote de distribuição finalizada com sucesso"


clean:
	@rm -rf $(SEI_SCRIPTS_DIR)/*;   if [ -d $(SEI_SCRIPTS_DIR) ]; then rmdir -p --ignore-fail-on-non-empty $(SEI_SCRIPTS_DIR); fi
	@rm -rf $(SEI_CONFIG_DIR)/*;    if [ -d $(SEI_CONFIG_DIR) ];  then rmdir -p --ignore-fail-on-non-empty $(SEI_CONFIG_DIR); fi
	@rm -rf $(SEI_BIN_DIR)/*;       if [ -d $(SEI_BIN_DIR) ];     then rmdir -p --ignore-fail-on-non-empty $(SEI_BIN_DIR); fi
	@rm -rf $(SEI_MODULO_DIR)/*;    if [ -d $(SEI_MODULO_DIR) ];  then rmdir -p --ignore-fail-on-non-empty $(SEI_MODULO_DIR); fi
	@rm -rf $(SIP_SCRIPTS_DIR)/*;   if [ -d $(SIP_SCRIPTS_DIR) ]; then rmdir -p --ignore-fail-on-non-empty $(SIP_SCRIPTS_DIR); fi
	@rm -f dist/$(PEN_MODULO_COMPACTADO)
	@echo "Limpeza do diretório de distribuição do realizada com sucesso"

config:  ## Configura o ambiente para outro banco de dados (mysql|sqlserver|oracle|postgresql). Ex: make config base=oracle 
	@cp -f $(PEN_TEST_FUNC)/env_$(base) $(PEN_TEST_FUNC)/.env;
	@echo "$(SUCCESS)Ambiente configurado para utilizar a base de dados $(base).$(NC)"


install: check-isalive
	$(CMD_COMPOSE_FUNC) up -d	
	$(CMD_COMPOSE_FUNC) exec org1-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	$(CMD_COMPOSE_FUNC) exec org1-http chown -R root:root /etc/cron.d/
	$(CMD_COMPOSE_FUNC) exec org1-http chmod 0644 /etc/cron.d/sei
	$(CMD_COMPOSE_FUNC) exec org1-http chmod 0644 /etc/cron.d/sip
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/web/modulos/pen org1-http bash -c './composer.phar update'
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/web/modulos/pen org2-http bash -c './composer.phar update'
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/web/modulos/pen org1-http bash -c './composer.phar install'
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/web/modulos/pen org2-http bash -c './composer.phar install'
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/scripts/$(MODULO_PASTAS_CONFIG) org1-http bash -c "$(CMD_INSTALACAO_SEI_MODULO)"
	$(CMD_COMPOSE_FUNC) exec -w /opt/sip/scripts/$(MODULO_PASTAS_CONFIG) org1-http bash -c "$(CMD_INSTALACAO_SIP_MODULO)" 

	$(CMD_COMPOSE_FUNC) exec org2-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	$(CMD_COMPOSE_FUNC) exec org2-http chown -R root:root /etc/cron.d/
	$(CMD_COMPOSE_FUNC) exec org2-http chmod 0644 /etc/cron.d/sei
	$(CMD_COMPOSE_FUNC) exec org2-http chmod 0644 /etc/cron.d/sip
	$(CMD_COMPOSE_FUNC) exec -w /opt/sei/scripts/$(MODULO_PASTAS_CONFIG) org2-http bash -c "$(CMD_INSTALACAO_SEI_MODULO)"
	$(CMD_COMPOSE_FUNC) exec -w /opt/sip/scripts/$(MODULO_PASTAS_CONFIG) org2-http bash -c "$(CMD_INSTALACAO_SIP_MODULO)" 

	wget -nc -i $(PEN_TEST_FUNC)/assets/arquivos/test_files_index.txt -P $(PEN_TEST_FUNC)/.tmp
	cp $(PEN_TEST_FUNC)/.tmp/* /tmp


.env:
	@if [ ! -f "$(PEN_TEST_FUNC)/.env" ]; then cp $(PEN_TEST_FUNC)/env_$(base) $(PEN_TEST_FUNC)/.env; fi

up: .env
	$(CMD_COMPOSE_FUNC) up -d


update: ## Atualiza banco de dados através dos scripts de atualização do sistema
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sei/scripts/ org1-http bash -c "$(CMD_INSTALACAO_SEI)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ org1-http bash -c "$(CMD_INSTALACAO_SIP)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ org1-http bash -c "$(CMD_INSTALACAO_RECURSOS_SEI)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sei/scripts/ org2-http bash -c "$(CMD_INSTALACAO_SEI)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ org2-http bash -c "$(CMD_INSTALACAO_SIP)"; true
	$(CMD_COMPOSE_FUNC) run --rm -w /opt/sip/scripts/ org2-http bash -c "$(CMD_INSTALACAO_RECURSOS_SEI)"; true


destroy: .env
	@if [ $(docker ps -a --filter="name=funcional-org1-http-1" | grep "Up" | awk '{split($0,a,"   "); print a[1]}') ]; then \
		$(CMD_COMPOSE_FUNC) exec org1-http bash -c "rm -rf /var/sei/arquivos/*"; \
		$(CMD_COMPOSE_FUNC) exec org2-http bash -c "rm -rf /var/sei/arquivos/*"; \
	fi; \
	$(CMD_COMPOSE_FUNC) down --volumes;


down: .env
	$(CMD_COMPOSE_FUNC) stop


# make teste=TramiteProcessoComDevolucaoTest test-functional
test-functional: .env $(FILE_VENDOR_FUNCIONAL) up vendor
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/phpunit -c /tests/phpunit.xml $(textdox) /tests/tests/$(addsuffix .php,$(teste)) ;


test-functional-parallel: .env $(FILE_VENDOR_FUNCIONAL) up
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/paratest -c /tests/phpunit.xml --testsuite $(TEST_SUIT) -p $(PARALLEL_TEST_NODES) $(TEST_GROUP_EXCLUIR) $(TEST_GROUP_INCLUIR)


test-parallel-otimizado: .env $(FILE_VENDOR_FUNCIONAL) up
	make -j2 test-functional-parallel tramitar-pendencias-silent
	
	
test-unit: .env $(FILE_VENDOR_UNITARIO)
	$(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_FUNC)/docker-compose.yml run --rm -w /tests php-test-unit bash -c 'XDEBUG_MODE=coverage vendor/bin/phpunit --testdox --coverage-html html rn/$(addsuffix .php,$(teste))'

test: test-unit test-functional

# Dependencias necessárias
# sudo apt-get install xmlstarlet -y; 
# sudo apt-get install gnome-screenshot -y;
# sudo apt-get install wmctrl -y;

# Uso do monitorar:
# Quando o parâmetro monitorar for igual a 1, o script acessa o segundo workspace e realiza o print da janela ativa.
# Assim sendo, para utilizá-lo corretamente, inicie a execução do script a seguir em uma janela de terminal localizada no segundo workspace.

# As evidências do script de teste a seguir serão geradas na pasta evidências do presente módulo SEI.

# make teste=MapeamentoEnvioParcialTest test-functional-especifico
# make teste=MapeamentoEnvioParcialTest test-functional-especifico parallel=1
# make teste=MapeamentoEnvioParcialTest test-functional-especifico parallel=1 monitorar=1
# make teste=MapeamentoEnvioParcialTest test-functional-especifico parallel=1 monitorar=1 tempo_pausa=3
test-functional-especifico:
	@clear; \
	time_start=$$(date +"%s"); \
	if [ "${parallel}" = 1 ]; then parallel=1; txt_parallel="parallel"; else parallel=0; fi; \
	if [ "${monitorar}" = 1 ]; then monitorar=1; else monitorar=0; fi; \
	if [ "${tempo_pausa}" = 1 ]; then tempo_pausa=1; else tempo_pausa=0; fi; \
	if [ "${teste}" = "" ]; then \
		echo "Informe o nome do teste a ser executado!"; \
		echo "\nEx1: make teste=MapeamentoEnvioParcialTest test-functional-especifico"; \
		exit 1; \
	fi; \
	if [ ! -e $(PEN_TST_FNC_EVD) ]; then mkdir $(PEN_TST_FNC_EVD); else rm -Rf $(PEN_TST_FNC_EVD)/*; fi; \
	if [ ! -e $(PEN_TST_FNC_EVD_TFE) ]; then mkdir $(PEN_TST_FNC_EVD_TFE); else rm -Rf $(PEN_TST_FNC_EVD_TFE)/*; fi; \
	echo "##### Classe de teste: test-functional-especifico "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	echo "##### Teste funcional: $(teste)\n"; \
	if [ "$${parallel}" = 1 ]; then tipo_teste="paratest"; nodes="-p $(PARALLEL_TEST_NODES)"; else tipo_teste="phpunit"; nodes=""; fi; \
	$(PEN_TEST_FUNC)/.env $(FILE_VENDOR_FUNCIONAL) up vendor; \
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/$${tipo_teste} -c /tests/phpunit.xml /tests/tests/$(addsuffix .php,$(teste)) \
		--log-junit /tests/evidencias/teste_funcional_especifico/$(NOME_ARQ_EVDNC_TFE).xml $${nodes}; \
	echo ""; docker ps --format "table {{.Image}}\t{{.Names}}\t{{.Status}}"; \
	echo "\n##### Classe de teste: test-functional-especifico "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	echo "##### Teste funcional: $(teste)"; \
	time_end=$$(date +"%s"); \
	time_elapsed=$$(($$((time_end))-$$((time_start)))); \
	echo "##### Tempo de processamento: "$$(date -d@$$((time_elapsed)) -u +%H:%M:%S)"\n"; \
	if [ "$${monitorar}" = 1 ]; then wmctrl -s 1; fi; \
	sleep "$${tempo_pausa}"; \
	flag_sucesso=0; flag_erro=0; \
	if [ -e $(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).xml ]; then \
		flag_sucesso=1; \
		qtde_assertions=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@assertions>0]/@assertions" -n $(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).xml); \
		if [ ! "$${qtde_assertions}" ]; then \
			flag_erro=1; \
		else \
			flag_erro=0; \
			if [ "$${qtde_assertions}" -gt 0 ]; then flag_sucesso=$$((flag_sucesso + 1)); fi; \
		fi; \
		qtde_errors=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@errors>0]/@errors" -n $(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).xml); \
		if [ "$${qtde_errors}" ]; then if [ "$${qtde_errors}" -gt 0 ]; then flag_erro=1; fi; fi; \
		qtde_failures=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@failures>0]/@failures" -n $(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).xml); \
		if [ "$${qtde_failures}" ]; then if [ "$${qtde_failures}" -gt 0 ]; then flag_erro=1; fi; fi; \
		if [ $$((flag_erro)) -eq 0 -a $$((flag_sucesso)) -ge 2 ]; then \
			echo "$$(tput setab 2)$$(tput setaf 0)##### Execução do teste funcional específico concluída com sucesso!$$(tput setaf 7)$$(tput setab 0)"; \
			echo "\n$$(tput setab 2)$$(tput setaf 0)##### Não foram detectados erros ou falsos positivos.$$(tput setab 0)$$(tput setaf 7)\n"; \
			gnome-screenshot -w --file=$(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).png; \
		else \
			flag_erro=1; \
		fi; \
	else \
		flag_erro=1; \
	fi; \
	if [ $$((flag_erro)) -gt 0 ]; then \
		echo "$$(tput setab 1)$$(tput setaf 7)##### Execução do teste funcional específico concluída.$$(tput setaf 7)$$(tput setab 0)"; \
		echo "\n$$(tput setab 1)$$(tput setaf 7)##### Detectados erros ou falsos positivos!$$(tput setab 0)$$(tput setaf 7)\n"; \
		gnome-screenshot -w --file=$(PEN_TST_FNC_EVD_TFE)/$(NOME_ARQ_EVDNC_TFE).png; \
	fi;

# Dependencias necessárias
# sudo apt-get install xmlstarlet -y; 
# sudo apt-get install gnome-screenshot -y;
# sudo apt-get install wmctrl -y;

# Uso do monitorar:
# Executa os testes em janela de terminal localizada no 2º Workspace do Ubuntu
# Após gerar a evidência, print da janela ativa do terminal, retorna ao 1º Workspace do Ubuntu
# Assim sendo, para utilizá-lo corretamente, inicie a execução do script a seguir
# em uma janela de terminal localizada no segundo workspace.

# Uso do tempo_pausa:
# Utilizado em conjunto com o parâmetro monitorar.
# Tempo de espera ao mudar de workspace para que se possa selecionar a janela do terminal de modo que o print da evidência de teste seja realizado corretamente.

# As evidências do script de teste a seguir serão geradas na pasta evidências do presente módulo SEI.
# Obs importane: não abrir os arquivos de evidências gerados antes da finalização dos testes! (isso pode causar falhas no resultado devido a cache dos arquivos)

# make test-functional-completo
# make test-functional-completo parallel=1
# make test-functional-completo parallel=1 monitorar=1
# make test-functional-completo parallel=1 monitorar=1 tempo_pausa=3
test-functional-completo:
	@clear; \
	time_start=$$(date +"%s"); \
	if [ "${parallel}" = 1 ]; then parallel=1; txt_parallel="parallel"; else parallel=0;	fi; \
	if [ "${monitorar}" = 1 ]; then monitorar=1; else monitorar=0; fi; \
	if [ "${tempo_pausa}" = 1 ]; then tempo_pausa=1; else tempo_pausa=0; fi; \
	if [ ! -e $(PEN_TST_FNC_EVD) ]; then mkdir $(PEN_TST_FNC_EVD); else rm -Rf $(PEN_TST_FNC_EVD)/*; fi; \
	if [ ! -e $(PEN_TST_FNC_EVD_TFC) ]; then mkdir $(PEN_TST_FNC_EVD_TFC); else rm -Rf $(PEN_TST_FNC_EVD_TFC)/*; fi; \
	if [ -e $(PEN_TST_FNC_TESTS_FS) ]; then rm -Rf $(PEN_TST_FNC_TESTS_FS); fi; \
	echo "##### Classe de teste: test-functional-completo "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	echo "##### Executando teste funcional completo...\n"; \
	if [ "$${parallel}" = 1 ]; then tipo_teste="paratest"; nodes="-p $(PARALLEL_TEST_NODES)"; else tipo_teste="phpunit"; nodes=""; fi; \
	$(PEN_TEST_FUNC)/.env $(FILE_VENDOR_FUNCIONAL) up vendor; \
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/$${tipo_teste} -c /tests/phpunit.xml \
		--log-junit /tests/evidencias/teste_funcional_completo/$(NOME_ARQ_EVDNC_TFC).xml --testsuite funcional $${nodes}; \
	echo ""; docker ps --format "table {{.Image}}\t{{.Names}}\t{{.Status}}"; \
	if [ "$${monitorar}" = 1 ]; then wmctrl -s 1; fi; \
	sleep $${tempo_pausa}; \
	echo "\n##### Classe de teste: test-functional-completo "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	time_end=$$(date +"%s"); \
	time_elapsed=$$(($$((time_end))-$$((time_start)))); \
	echo "##### Tempo de processamento: "$$(date -d@$$((time_elapsed)) -u +%H:%M:%S)"\n"; \
	if [ ! -s $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml ]; then \
		qtde_tests=0; \
	else \
		qtde_tests=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@tests>0]/@tests" -n $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml); \
	fi; \
	if [ $${qtde_tests} -eq 0 ]; then \
		echo "\n$$(tput setab 1)$$(tput setaf 7)##### Resultado do Teste funcional completo não localizado.$$(tput setab 0)$$(tput setaf 7)\n"; \
		echo "$$(tput setab 1)$$(tput setaf 7)##### Execute o teste novamente!$$(tput setab 0)$$(tput setaf 7)\n"; \
		exit 1; \
	fi; \
	flag_sucesso=0; flag_erro=0; \
	if [ -e $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml ]; then flag_sucesso=1; else flag_erro=1; fi; \
	qtde_assertions=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@assertions>0]/@assertions" -n $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml); \
	if [ ! "$${qtde_assertions}" ]; then flag_erro=1; else flag_erro=0; if [ "$${qtde_assertions}" -gt 0 ]; then flag_sucesso=$$((flag_sucesso + 1)); fi; fi; \
	qtde_errors=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@errors>0]/@errors" -n $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml); \
	if [ "$${qtde_errors}" ]; then if [ "$${qtde_errors}" -gt 0 ]; then flag_erro=1; fi; fi; \
	qtde_failures=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@failures>0]/@failures" -n $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml); \
	if [ "$${qtde_failures}" ]; then if [ "$${qtde_failures}" -gt 0 ]; then flag_erro=1; fi; fi; \
	if [ $$((flag_erro)) -eq 0 -a $$((flag_sucesso)) -ge 2 ]; then \
		echo "\n$$(tput setab 2)$$(tput setaf 0)##### Execução do teste funcional completo concluída com sucesso!$$(tput setaf 7)$$(tput setab 0)"; \
		echo "\n$$(tput setab 2)$$(tput setaf 0)##### Não foram detectados erros ou falsos positivos.$$(tput setab 0)$$(tput setaf 7)\n"; \
		gnome-screenshot -w --file=$(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).png; \
	else \
		if [ "$${parallel}" = 1 ]; then xml_path="/testsuites/testsuite/testsuite"; else xml_path="/testsuites/testsuite/testsuite/testsuite"; fi; \
		xmlstarlet sel -t -v "$${xml_path}[@errors>0]/@file" -n \
			-t -v "$${xml_path}[@failures>0]/@file" -n \
			$(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml \
			| uniq | sed 's,'/tests/tests/',,g' \
			> $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).txt; \
		if [ ! -e $(PEN_TST_FNC_TESTS_FS) ]; then mkdir $(PEN_TST_FNC_TESTS_FS); else rm -Rf $(PEN_TST_FNC_TESTS_FS)/*; fi; \
		cp $(PEN_TEST_FUNC)/tests/CenarioBaseTestCase.php $(PEN_TST_FNC_TESTS_FS)/CenarioBaseTestCase.php; \
		while read arquivo; do \
			if [ ! $$arquivo = "" ]; then \
				cp $(PEN_TEST_FUNC)/tests/$$arquivo $(PEN_TST_FNC_TESTS_FS)/$$arquivo; \
			fi; \
		done < $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).txt; \
		echo "\n$$(tput setab 1)$$(tput setaf 7)##### Execução do teste funcional completo concluída.$$(tput setaf 7)$$(tput setab 0)"; \
		echo "\n$$(tput setab 1)$$(tput setaf 7)##### Detectados erros ou falsos positivos!$$(tput setab 0)$$(tput setaf 7)\n"; \
		gnome-screenshot -w --file=$(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).png; \
		if [ "$${monitorar}" = 1 ]; then wmctrl -s 0; fi; \
		make -s test-functional-falsos-positivos parallel="$${parallel}" monitorar="$${monitorar}" tempo_pausa="$${tempo_pausa}"; \
	fi;

# Dependencias necessárias
# sudo apt-get install xmlstarlet -y; 
# sudo apt-get install gnome-screenshot -y;
# sudo apt-get install wmctrl -y;

# Uso do monitorar:
# Executa os testes em janela de terminal localizada no 2º Workspace do Ubuntu
# Após gerar a evidência, print da janela ativa do terminal, retorna ao 1º Workspace do Ubuntu
# Assim sendo, para utilizá-lo corretamente, inicie a execução do script a seguir
# em uma janela de terminal localizada no segundo workspace.

# Uso do tempo_pausa:
# Utilizado em conjunto com o parâmetro monitorar.
# Tempo de espera ao mudar de workspace para que se possa selecionar a janela do terminal de modo que o print da evidência de teste seja realizado corretamente.

# As evidências do script de teste a seguir serão geradas na pasta evidências do presente módulo SEI.

# make test-functional-falsos-positivos
# make test-functional-falsos-positivos parallel=1
# make test-functional-falsos-positivos parallel=1 monitorar=1
# make test-functional-falsos-positivos parallel=1 monitorar=1 tempo_pausa=3
test-functional-falsos-positivos:
	@clear; \
	time_start=$$(date +"%s"); \
	if [ "${parallel}" = 1 ]; then parallel=1; txt_parallel="parallel"; else parallel=0; fi; \
	if [ "${monitorar}" = 1 ]; then monitorar=1; else monitorar=0; fi; \
	if [ "${tempo_pausa}" = 1 ]; then tempo_pausa=1; else tempo_pausa=0; fi; \
	for i in `seq 1 5`; do if [ ! -e $(PEN_TST_FNC_EVD_TFP)-$$i ]; then ultima_execucao=$$((i - 1)); break; fi;	done; \
	execucao=$$((ultima_execucao + 1)); \
	echo "##### Classe de teste: test-functional-falsos-positivos "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	echo "##### Executando teste funcional falsos positivos pela $${execucao}ª vez...\n"; \
	if [ $${execucao} -eq 1 ]; then \
		if [ ! -s $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml ]; then \
			qtde_tests=0; \
		else \
			qtde_tests=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@tests>0]/@tests" -n $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).xml); \
		fi; \
	else \
		if [ ! -s $(PEN_TST_FNC_EVD_TFP)-$${ultima_execucao}/$(NOME_ARQ_EVDNC_TFP)-$${ultima_execucao}.xml ]; then \
			qtde_tests=0; \
		else \
			qtde_tests=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@tests>0]/@tests" -n $(PEN_TST_FNC_EVD_TFP)-$${ultima_execucao}/$(NOME_ARQ_EVDNC_TFP)-$${ultima_execucao}.xml); \
		fi; \
	fi; \
	if [ ! "$${qtde_tests}" ]; then qtde_tests=0; fi; \
	if [ $${qtde_tests} -eq 0 ]; then \
		if [ $${execucao} -eq 1 ]; then \
			echo "$$(tput setab 1)$$(tput setaf 7)##### Resultado do Teste funcional completo não localizado.$$(tput setab 0)$$(tput setaf 7)\n"; \
			echo "$$(tput setab 1)$$(tput setaf 7)##### Execute o teste funcional completo novamente!$$(tput setab 0)$$(tput setaf 7)\n"; \
		else \
			echo "$$(tput setab 1)$$(tput setaf 7)##### Resultado do Teste funcional falsos positivos anterior não localizado.$$(tput setab 0)$$(tput setaf 7)\n"; \
		fi; \
		exit 1; \
	fi; \
	for i in `seq $$((execucao)) 5`; do rm -Rf $(PEN_TST_FNC_EVD_TFP)-$$i; done; \
	if [ ! -e $(PEN_TST_FNC_TESTS_FS) ]; then mkdir $(PEN_TST_FNC_TESTS_FS); else rm -Rf $(PEN_TST_FNC_TESTS_FS)/*; fi; \
	cp $(PEN_TEST_FUNC)/tests/CenarioBaseTestCase.php $(PEN_TST_FNC_TESTS_FS)/CenarioBaseTestCase.php; \
	while read arquivo; do \
		if [ ! $$arquivo = "" ]; then \
			cp $(PEN_TEST_FUNC)/tests/$$arquivo $(PEN_TST_FNC_TESTS_FS)/$$arquivo; \
		fi; \
	done < $(PEN_TST_FNC_EVD_TFC)/$(NOME_ARQ_EVDNC_TFC).txt; \
	if [ ! -e $(PEN_TST_FNC_EVD_TFP)-$${execucao} ]; then mkdir $(PEN_TST_FNC_EVD_TFP)-$${execucao}; else rm -Rf $(PEN_TST_FNC_EVD_TFP)-$${execucao}/*; fi; \
	if [ "$${parallel}" = 1 ]; then tipo_teste="paratest"; nodes="-p$(PARALLEL_TEST_NODES)"; else tipo_teste="phpunit"; nodes=""; fi; \
	$(PEN_TEST_FUNC)/.env $(FILE_VENDOR_FUNCIONAL) up vendor; \
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/$${tipo_teste} -c /tests/phpunit.xml --testsuite falsos_positivos \
		--log-junit /tests/evidencias/teste_funcional_falsos_positivos-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml $${nodes}; \
	echo "\n"; docker ps --format "table {{.Image}}\t{{.Names}}\t{{.Status}}"; \
	if [ -e $(PEN_TST_FNC_TESTS_FS) ]; then rm -Rf $(PEN_TST_FNC_TESTS_FS)/*; fi; \
	if [ "$${monitorar}" = 1 ]; then wmctrl -s 1; fi; \
	sleep "$${tempo_pausa}"; \
	echo "\n##### Classe de teste: test-functional-falsos-positivos "$${txt_parallel}; \
	echo -n "##### Branch atual: "; git branch --show-current; \
	time_end=$$(date +"%s"); \
	time_elapsed=$$(($$((time_end))-$$((time_start)))); \
	echo "##### Tempo de processamento: "$$(date -d@$$((time_elapsed)) -u +%H:%M:%S)"\n"; \
	if [ ! -s $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml ]; then \
		qtde_tests=0; \
	else \
		qtde_tests=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@tests>0]/@tests" -n $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml); \
	fi; \
	if [ $${qtde_tests} -eq 0 ]; then \
		echo "\n$$(tput setab 1)$$(tput setaf 7)##### Resultado do Teste funcional falsos positivos não localizado.$$(tput setab 0)$$(tput setaf 7)\n"; \
		echo "$$(tput setab 1)$$(tput setaf 7)##### Execute o teste novamente!$$(tput setab 0)$$(tput setaf 7)\n"; \
		exit 1; \
	fi; \
	flag_sucesso=0;	flag_erro=0; \
	if [ -e $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml ]; then flag_sucesso=1; else flag_erro=1; fi; \
	qtde_assertions=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@assertions>0]/@assertions" -n $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml); \
	if [ ! "$${qtde_assertions}" ]; then qtde_assertions=0; fi; \
	if [ "$${qtde_assertions}" -gt 0 ]; then flag_sucesso=$$((flag_sucesso + 1)); fi; \
	qtde_errors=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@errors>0]/@errors" -n $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml); \
	if [ ! "$${qtde_errors}" ]; then qtde_errors=0; fi; \
	if [ "$${qtde_errors}" -gt 0 ]; then flag_erro=1; fi; \
	qtde_failures=$$(xmlstarlet sel -t -v "/testsuites/testsuite[@failures>0]/@failures" -n $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml); \
	if [ ! "$${qtde_failures}" ]; then qtde_failures=0; fi; \
	if [ "$${qtde_failures}" -gt 0 ]; then flag_erro=1; fi; \
	if [ $$((flag_erro)) -eq 0 -a $$((flag_sucesso)) -ge 2 ]; then \
		echo "\n$$(tput setab 2)$$(tput setaf 0)##### $${execucao}ª execução do teste funcional falsos positivos concluída com sucesso!$$(tput setaf 7)$$(tput setab 0)"; \
		echo "\n$$(tput setab 2)$$(tput setaf 0)##### Não foram detectados erros ou falsos positivos.$$(tput setab 0)$$(tput setaf 7)\n"; \
		gnome-screenshot -w --file="$(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.png"; \
	else \
		if [ "$${parallel}" = 1 ]; then xml_path="/testsuites/testsuite/testsuite"; else xml_path="/testsuites/testsuite/testsuite/testsuite"; fi; \
		xmlstarlet sel -t -v "$${xml_path}[@errors>0]/@file" -n \
			-t -v "$${xml_path}[@failures>0]/@file" -n \
			$(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.xml \
			| uniq | sed 's,'/tests/tests_funcional_falsos_positivos_temp/',,g' \
			> $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.txt; \
		if [ ! -e $(PEN_TST_FNC_TESTS_FS) ]; then mkdir $(PEN_TST_FNC_TESTS_FS); fi; \
		cp $(PEN_TEST_FUNC)/tests/CenarioBaseTestCase.php $(PEN_TST_FNC_TESTS_FS)/CenarioBaseTestCase.php; \
		while read arquivo; do \
			if [ ! $$arquivo = "" ]; then \
				cp $(PEN_TEST_FUNC)/tests/$$arquivo $(PEN_TST_FNC_TESTS_FS)/$$arquivo; \
			fi; \
		done < $(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.txt; \
			echo "\n$$(tput setab 1)$$(tput setaf 7)##### $${execucao}ª execução do teste funcional falsos positivos concluída.$$(tput setaf 7)$$(tput setab 0)"; \
			echo "\n$$(tput setab 1)$$(tput setaf 7)##### Detectados erros ou falsos positivos!$$(tput setab 0)$$(tput setaf 7)\n"; \
		if [ $${execucao} -lt 5 ]; then \
			gnome-screenshot -w --file="$(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.png"; \
			if [ "$${monitorar}" = 1 ]; then wmctrl -s 0; fi; \
			make -s test-functional-falsos-positivos parallel="$${parallel}" monitorar="$${monitorar}" tempo_pausa="$${tempo_pausa}"; \
		else \
			echo "$$(tput setab 1)$$(tput setaf 7)##### Limite máximo de 5 tentativas de testes alcançado!$$(tput setab 0)\n"; \
			gnome-screenshot -w --file="$(PEN_TST_FNC_EVD_TFP)-$${execucao}/$(NOME_ARQ_EVDNC_TFP)-$${execucao}.png"; \
		fi; \
	fi;

verify-config:
	@echo "Verificando configurações do módulo para instância org1"
	$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
	@echo "Verificando configurações do módulo para instância org2"
	$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php

bash_org1:
	$(CMD_COMPOSE_FUNC) exec org1-http bash
	

bash_org2:
	$(CMD_COMPOSE_FUNC) exec org2-http bash

atualizaSequencia:
	$(CMD_COMPOSE_FUNC) exec org1-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 
	$(CMD_COMPOSE_FUNC) exec org2-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 

tramitar-pendencias:
	i=1; while [ "$$i" -le 2 ]; do \
    	echo "Executando T1 $$i"; \
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php;
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php & \
		$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php;
		$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php; \
		i=$$((i + 1));\
  	done & i=1; while [ "$$i" -le 2 ]; do \
    	echo "Executando T2 $$i"; \
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php;
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php & \
		$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php;
		$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php; \
		i=$$((i + 1));\
  	done

tramitar-pendencias-simples: tramitar-pendencias-simples-org1 tramitar-pendencias-simples-org2
	@$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php;

tramitar-pendencias-simples-org1:
	@$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php; \
	$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php;

tramitar-pendencias-simples-org2:
	@$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php; \
	$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php;

tramitar-pendencias-silent:
	@echo 'Executando monitoramento de pendências do Org1 e Org2'
	@i=1; while [ "$$i" -le 3000 ]; do \
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php; \
		$(CMD_COMPOSE_FUNC) exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php; \
		$(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoEnvioTarefasPEN.php; \
	  $(CMD_COMPOSE_FUNC) exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoRecebimentoTarefasPEN.php; \
		i=$$((i + 1));\
  	done 

#deve ser rodado em outro terminal
stop-test-container:
	@if [ $$(docker ps -a -q --filter="name=funcional-php-test-functional-run" | wc -l) ]; then \
		docker stop $$(docker ps -a -q --filter="name=php-test"); \
	fi;

vendor: composer.json
	$(CMD_COMPOSE_FUNC) run -w /tests php-test-functional bash -c './composer.phar install'

.PHONY: .env help clean dist all install destroy up update down test test-functional test-functional-parallel test-unit bash_org1 bash_org2 verify-config

# Parâmetros de execução do comando MAKE
# Opções possíveis para spe (sistema de proc eletronico): sei3, sei4, sei41, super
sistema=sei41
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

dist: cria_json_compatibilidade
	# ATENÇÃO: AO ADICIONAR UM NOVO ARQUIVO DE DEPLOY, VERIFICAR O MESMO EM VerificadorInstalacaoRN::verificarPosicionamentoScriptsConectado
	@mkdir -p $(SEI_SCRIPTS_DIR)
	@mkdir -p $(SEI_CONFIG_DIR)
	@mkdir -p $(SEI_BIN_DIR)
	@mkdir -p $(SEI_MODULO_DIR)
	@mkdir -p $(SIP_SCRIPTS_DIR)
	@php composer.phar install --no-dev
	@cp -R src/* $(SEI_MODULO_DIR)/
	@cp docs/INSTALL.md dist/INSTALACAO.md
	@cp docs/UPGRADE.md dist/ATUALIZACAO.md
	@cp docs/changelogs/CHANGELOG-$(VERSAO_MODULO).md dist/NOTAS_VERSAO.md
	@cp compatibilidade.json dist/compatibilidade.json
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
	@cd dist/ && zip -r $(PEN_MODULO_COMPACTADO) INSTALACAO.md ATUALIZACAO.md NOTAS_VERSAO.md compatibilidade.json sei/ sip/
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

.env:
	@if [ ! -f "$(PEN_TEST_FUNC)/.env" ]; then cp $(PEN_TEST_FUNC)/env_$(base) $(PEN_TEST_FUNC)/.env; fi

up: .env prepare-upload-tmp 
	$(CMD_COMPOSE_FUNC) up -d 
	make prepare-files-permissions

prepare-upload-tmp:
	@if [ ! -d "$(PEN_TEST_FUNC)/.tmp" ]; then \
		echo "📁 Criando diretório .tmp..."; \
		mkdir -p "$(PEN_TEST_FUNC)/.tmp"; \
		chmod -R 777 "$(PEN_TEST_FUNC)/.tmp"; \
		wget -nc -i "$(PEN_TEST_FUNC)/assets/arquivos/test_files_index.txt" -P "$(PEN_TEST_FUNC)/.tmp" || { \
			echo "\n   ❌ Erro ao baixar arquivos com wget. Verifique o caminho ou a conexão. \n"; \
			exit 1; \
		}; \
		echo "\n   ✅ Arquivos baixados com sucesso.\n"; \
	else \
		echo "\n   ℹ️  Diretório .tmp já existe, pulando download.\n"; \
	fi

prepare-files-permissions:
	@printf "\n⌛ Aguardando criação do container no org1 para dar permissões de pastas...\n"
	$(CMD_COMPOSE_FUNC) exec org1-http sh -c 'while [ ! -d /var/sei/arquivos ]; do sleep 1; done; chmod -R 777 /var/sei/arquivos'

	@printf "\n⌛ Aguardando criação do container no org2 para dar permissões de pastas...\n"
	$(CMD_COMPOSE_FUNC) exec org2-http sh -c 'while [ ! -d /var/sei/arquivos ]; do sleep 1; done; chmod -R 777 /var/sei/arquivos'

	@printf "\n   ✅ Permissões de pastas ajustadas com sucesso.\n\n"


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
	@printf "\n⌛ Aguardando criação do container no test-functional para dar permissões de pastas...\n"
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional sh -c 'while [ ! -d /var/sei/arquivos ]; do sleep 1; done; chmod -R 777 /var/sei/arquivos'

	@printf "\n   ✅ Permissões de pastas para testes ajustadas com sucesso.\n\n"

	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/phpunit -c /tests/phpunit.xml --testdox /tests/tests/$(addsuffix .php,$(teste))


test-functional-parallel: .env $(FILE_VENDOR_FUNCIONAL) up
	$(CMD_COMPOSE_FUNC) run --rm php-test-functional /tests/vendor/bin/paratest -c /tests/phpunit.xml --testsuite $(TEST_SUIT) -p $(PARALLEL_TEST_NODES) $(TEST_GROUP_EXCLUIR) $(TEST_GROUP_INCLUIR)


test-parallel-otimizado: .env $(FILE_VENDOR_FUNCIONAL) up
	make -j2 test-functional-parallel tramitar-pendencias-silent
	
	
test-unit: .env $(FILE_VENDOR_UNITARIO)
	$(CMD_DOCKER_COMPOSE) -f $(PEN_TEST_FUNC)/docker-compose.yml run --rm -w /tests php-test-unit bash -c 'XDEBUG_MODE=coverage vendor/bin/phpunit --testdox --coverage-html html rn/$(addsuffix .php,$(teste))'

test: test-unit test-functional


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
	$(CMD_COMPOSE_FUNC) run --rm -w /tests php-test-functional bash -c './composer.phar install'

cria_json_compatibilidade:
	$(shell ./gerar_json_compatibilidade.sh)

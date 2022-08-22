.PHONY: .env help update clean dist all install destroy up down test test-functional test-functional-parallel test-unit bash_org1 bash_org2 verify-config




# Parâmetros de execução do comando MAKE
versao_sei=4
teste=
base = mysql

MODULO_NOME = pen
MODULO_PASTAS_CONFIG = mod-$(MODULO_NOME)

VERSAO_MODULO := $(shell grep 'define."VERSAO_MODULO_PEN"' src/PENIntegracao.php | cut -d'"' -f4)
SEI_SCRIPTS_DIR = dist/sei/scripts/mod-pen
SEI_CONFIG_DIR = dist/sei/config/mod-pen
SEI_BIN_DIR = dist/sei/bin/mod-pen
SEI_MODULO_DIR = dist/sei/web/modulos/pen
SIP_SCRIPTS_DIR = dist/sip/scripts/mod-pen
PEN_MODULO_COMPACTADO = mod-sei-pen-$(VERSAO_MODULO).zip
PEN_TEST_FUNC = tests_sei$(versao_sei)/funcional
PEN_TEST_UNIT = tests_sei$(versao_sei)/unitario
PARALLEL_TEST_NODES = 5
include $(PEN_TEST_FUNC)/.env

CMD_INSTALACAO_SEI_PEN = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php sei_atualizar_versao_modulo_pen.php
CMD_INSTALACAO_SIP_PEN = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php sip_atualizar_versao_modulo_pen.php


CMD_INSTALACAO_SEI = echo -ne '$(SEI_DATABASE_USER)\n$(SEI_DATABASE_PASSWORD)\n' | php atualizar_versao_sei.php
CMD_INSTALACAO_SIP = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_versao_sip.php
CMD_INSTALACAO_RECURSOS_SEI = echo -ne '$(SIP_DATABASE_USER)\n$(SIP_DATABASE_PASSWORD)\n' | php atualizar_recursos_sei.php


all: help

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
	@mv $(SEI_MODULO_DIR)/scripts/MonitoramentoTarefasPEN.php $(SEI_SCRIPTS_DIR)/
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



install: update  ## Instala e atualiza a base de dados com as tabelas do módulo
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d	
	@echo "Sleeping for 20 seconds ..."; sleep 20;
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chown -R root:root /etc/cron.d/
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chmod 0644 /etc/cron.d/sei
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chmod 0644 /etc/cron.d/sip
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec -T -w /opt/sei/scripts/$(MODULO_PASTAS_CONFIG) org1-http bash -c "$(CMD_INSTALACAO_SEI_PEN)";
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec -T -w /opt/sip/scripts/$(MODULO_PASTAS_CONFIG) org1-http bash -c "$(CMD_INSTALACAO_SIP_PEN)";

	# docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
	# docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chown -R root:root /etc/cron.d/
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chmod 0644 /etc/cron.d/sei
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chmod 0644 /etc/cron.d/sip
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec -T -w /opt/sei/scripts/$(MODULO_PASTAS_CONFIG) org2-http bash -c "$(CMD_INSTALACAO_SEI_PEN)";
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec -T -w /opt/sip/scripts/$(MODULO_PASTAS_CONFIG) org2-http bash -c "$(CMD_INSTALACAO_SIP_PEN)";

	# docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
	# docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
	wget -nc -i $(PEN_TEST_FUNC)/assets/arquivos/test_files_index.txt -P $(PEN_TEST_FUNC)/.tmp
	cp $(PEN_TEST_FUNC)/.tmp/* /tmp
	./composer.phar install -d $(PEN_TEST_FUNC)
	./composer.phar install -d $(PEN_TEST_UNIT)

update: ## Atualiza banco de dados através dos scripts de atualização do sistema
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sei/scripts/ org1-http bash -c "$(CMD_INSTALACAO_SEI)"; true
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sip/scripts/ org1-http bash -c "$(CMD_INSTALACAO_SIP)"; true
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sip/scripts/ org1-http bash -c "$(CMD_INSTALACAO_RECURSOS_SEI)"; true

	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sei/scripts/ org2-http bash -c "$(CMD_INSTALACAO_SEI)"; true
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sip/scripts/ org2-http bash -c "$(CMD_INSTALACAO_SIP)"; true
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm -w /opt/sip/scripts/ org2-http bash -c "$(CMD_INSTALACAO_RECURSOS_SEI)"; true
	

config:  ## Configura o ambiente para outro banco de dados (mysql|sqlserver|oracle). Ex: make config base=oracle 
	@cp -f $(PEN_TEST_FUNC)/.env_$(base) $(PEN_TEST_FUNC)/.env
	@echo "Ambiente configurado para utilizar a base de dados $(base). (base=[mysql|oracle|sqlserver])"


verify-config:
	@echo "Verificando configurações do módulo para instância org1"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
	@echo "Verificando configurações do módulo para instância org2"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php


destroy: ## Destrói ambiente de desenvolvimento local, junto com os dados armazenados em banco de dados
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env down --volumes


up:	 ## Inicia ambiente de desenvolvimento local (docker) no endereço http://localhost:8000
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d


down:	 ## Interrompe execução do ambiente de desenvolvimento local em docker
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env stop


# make teste=TramiteProcessoComDevolucaoTest test-functional
test-functional:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm php-test-functional /tests/vendor/bin/phpunit -c /tests/phpunit.xml /tests/tests/$(addsuffix .php,$(teste))


test-functional-parallel:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm php-test-functional /tests/vendor/bin/paratest -c /tests/phpunit.xml --testsuite funcional -p 8


test-parallel-otimizado:
	make -j2 test-functional-parallel tramitar-pendencias-silent
	
	
test-unit:
	php -c php.ini $(PEN_TEST_UNIT)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_UNIT)/phpunit.xml $(PEN_TEST_UNIT)/rn/ProcessoEletronicoRNTest.php 


test: test-unit test-functional


bash_org1:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http bash
	

bash_org2:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http bash

atualizaSequencia:
	docker exec -it org1-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 
	docker exec -it org2-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 

deletarHttpProxy:
	docker container rm proxy org1-http org2-http

tramitar-pendencias:
	i=1; while [ "$$i" -le 2 ]; do \
    	echo "Executando T1 $$i"; \
		docker exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php & \
		docker exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php; \
		i=$$((i + 1));\
  	done & i=1; while [ "$$i" -le 2 ]; do \
    	echo "Executando T2 $$i"; \
		docker exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php & \
		docker exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php; \
		i=$$((i + 1));\
  	done

tramitar-pendencias-silent:
	i=1; while [ "$$i" -le 300 ]; do \
    	echo "Executando $$i" >/dev/null 2>&1; \
		docker exec org1-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php >/dev/null 2>&1 & \
		docker exec org2-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php >/dev/null 2>&1; \
		i=$$((i + 1));\
  	done 

#deve ser rodado em outro terminal
stop-test-container:
	docker stop $$(docker ps -a -q --filter="name=php-test")


help:
	@echo "Usage: make [target] ... \n"
	@grep -E '^[a-zA-Z_-]+[[:space:]]*:.*?## .*$$' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'


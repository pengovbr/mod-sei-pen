.PHONY: .env help clean build all test-environment-provision test-environment-destroy test-environment-up test-environment-down test test-functional test-functional-parallel test-unit bash_org1 bash_org2 verify-config

include tests/funcional/.env

VERSAO_MODULO := $(shell grep 'const VERSAO_MODULO' src/PENIntegracao.php | cut -d'"' -f2)

SEI_SCRIPTS_DIR = dist/sei/scripts/mod-pen
SEI_CONFIG_DIR = dist/sei/config/mod-pen
SEI_BIN_DIR = dist/sei/bin/mod-pen
SEI_MODULO_DIR = dist/sei/web/modulos/pen
SIP_SCRIPTS_DIR = dist/sip/scripts/mod-pen
PEN_MODULO_COMPACTADO = mod-sei-pen-$(VERSAO_MODULO).zip
PEN_TEST_FUNC = tests/funcional
PEN_TEST_UNIT = tests/unitario
PARALLEL_TEST_NODES = 5

all: clean build

build: 
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


install: 
	unzip -o -d $(SEI_PATH) dist/$(PEN_MODULO_COMPACTADO)

install-dev: 
	ln -sf $(PWD)/src/bin $(SEI_PATH)/sei/bin/mod-pen
	ln -sf $(PWD)/src/config $(SEI_PATH)/sei/config/mod-pen
	ln -sf $(PWD)/src/scripts $(SEI_PATH)/sei/scripts/mod-pen
	ln -sf $(PWD)/src/ $(SEI_PATH)/sei/web/modulos/pen
	

test-environment-provision:	
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d	
	@echo "Sleeping for 45 seconds ..."; sleep 45;
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chown -R root:root /etc/cron.d/
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chmod 0644 /etc/cron.d/sei
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http chmod 0644 /etc/cron.d/sip
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http bash -c "printenv | sed 's/^\(.*\)$$/export \1/g' > /root/crond_env.sh"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chown -R root:root /etc/cron.d/
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chmod 0644 /etc/cron.d/sei
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http chmod 0644 /etc/cron.d/sip
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sei/scripts/mod-pen/sei_atualizar_versao_modulo_pen.php
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sip/scripts/mod-pen/sip_atualizar_versao_modulo_pen.php
	wget -nc -i $(PEN_TEST_FUNC)/assets/arquivos/test_files_index.txt -P $(PEN_TEST_FUNC)/.tmp
	cp $(PEN_TEST_FUNC)/.tmp/* /tmp
	./composer.phar install -d $(PEN_TEST_FUNC)
	./composer.phar install -d $(PEN_TEST_UNIT)


verify-config:
	@echo "Verificando configurações do módulo para instância org1"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php
	@echo "Verificando configurações do módulo para instância org2"
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http php /opt/sei/scripts/mod-pen/verifica_instalacao_modulo_pen.php

test-environment-destroy:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env stop
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env rm


test-environment-up:	
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d
	# TODO: mover rotina abaixo para target específico de atualização de base de dados do sistema para
	# não solicitar senha de atualização toda vez que subir ambiente. Base de referência utilizada para testes
	# não possui problemas de sequences, o que pode ser necessário para backups restaurados com problemas	
	# @echo "Sleeping for 5 seconds ..."; sleep 5;
	# docker exec -it org1-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 
	# docker exec -it org2-http php -c /opt/php.ini /opt/sip/scripts/atualizar_sequencias.php


test-environment-down:	
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env stop


test-functional:
	$(PEN_TEST_FUNC)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_FUNC)/phpunit.xml --testsuite funcional


test-functional-internal:	
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm php-test-functional /tests/vendor/phpunit/phpunit/phpunit -c /tests/phpunit.xml  --testsuite funcional


test-functional-parallel:
	$(PEN_TEST_FUNC)/vendor/bin/paratest -c $(PEN_TEST_FUNC)/phpunit.xml --testsuite funcional -p 4
	  

test-functional-parallel-internal:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm php-test-functional /tests/vendor/bin/paratest -c /tests/phpunit.xml --bootstrap /tests/bootstrap.php --testsuite funcional -p 4


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

#comando para executar apenas 1 teste
# make teste=TramiteProcessoComDevolucaoTest run-test-xdebug
run-test-xdebug:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env run --rm php-test-functional /tests/vendor/phpunit/phpunit/phpunit -c /tests/phpunit.xml /tests/tests_sei4/$(addsuffix .php,$(teste))

#deve ser rodado em outro terminal
stop-test-container:
	docker stop $$(docker ps -a -q --filter="name=php-test")

# make teste=TramiteProcessoComDevolucaoTest sei3-run-test-xdebug
sei3-run-test-xdebug:
	$(PEN_TEST_FUNC)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_FUNC)/phpunit.xml --stop-on-failure $(PEN_TEST_FUNC)/tests/$(addsuffix .php,$(teste))


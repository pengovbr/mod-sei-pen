.PHONY: .env help clean build all test-environment-provision test-environment-destroy test-environment-up test-environment-down test test-functional test-functional-parallel test-unit bash_org1 bash_org2

include tests/funcional/.env

HOST_IP := $(shell hostname -I | cut -d' ' -f1)
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
	export HOST_IP=$(HOST_IP); docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d	
	@echo "Sleeping for 60 seconds ..."; sleep 60;
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
	wget -nc -i $(PEN_TEST_FUNC)/assets/arquivos/test_files_index.txt -P /tmp
	composer install -d $(PEN_TEST_FUNC)
	composer install -d $(PEN_TEST_UNIT)


test-environment-destroy:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env stop
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env rm


test-environment-up:	
	export HOST_IP=$(HOST_IP); docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env up -d	
	@echo "Sleeping for 5 seconds ..."; sleep 5;
	sudo docker exec -it org1-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 


test-environment-down:	
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env stop


test-functional:
	$(PEN_TEST_FUNC)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_FUNC)/phpunit.xml --stop-on-failure  --testsuite funcional --exclude-group testePesado

test-functional-pesado:
	$(PEN_TEST_FUNC)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_FUNC)/phpunit.xml --stop-on-failure  --testsuite funcional --group testePesado

test-functional-parallel:
	$(PEN_TEST_FUNC)/vendor/bin/paratest -c $(PEN_TEST_FUNC)/phpunit.xml --bootstrap $(PEN_TEST_FUNC)/bootstrap.php --testsuite funcional --exclude-group testePesado -p 4
	  
	
test-unit:
	php -c php.ini $(PEN_TEST_UNIT)/vendor/phpunit/phpunit/phpunit -c $(PEN_TEST_UNIT)/phpunit.xml $(PEN_TEST_UNIT)/rn/ProcessoEletronicoRNTest.php 


test: test-unit test-functional


bash_org1:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org1-http bash
	

bash_org2:
	docker-compose -f $(PEN_TEST_FUNC)/docker-compose.yml --env-file $(PEN_TEST_FUNC)/.env exec org2-http bash

atualizaSequencia:
	sudo docker exec -it org1-http php -c /opt/php.ini /opt/sei/scripts/atualizar_sequencias.php 

tramitar-pendencias:
	i=1; while [ "$$i" -le 2 ]; do \
	echo "Executando $$i"; \
			sudo docker exec -it org1-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php; \
			sudo docker exec -it org2-http php /opt/sei/scripts/mod-pen/MonitoramentoTarefasPEN.php; \
			i=$$((i + 1));\
	done
               
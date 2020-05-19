.PHONY: help clean build test all

SEI_SCRIPTS_DIR = dist/sei/scripts/mod-pen
SEI_CONFIG_DIR = dist/sei/config/mod-pen
SEI_BIN_DIR = dist/sei/bin/mod-pen
SEI_MODULO_DIR = dist/sei/web/modulos/pen
SIP_SCRIPTS_DIR = dist/sip/scripts/mod-pen
PEN_MODULO_COMPACTADO = mod-sei-pen-VERSAO.tar.gz

all: clean build

build: clean
	@mkdir -p $(SEI_SCRIPTS_DIR)
	@mkdir -p $(SEI_CONFIG_DIR)
	@mkdir -p $(SEI_BIN_DIR)
	@mkdir -p $(SEI_MODULO_DIR)
	@mkdir -p $(SIP_SCRIPTS_DIR)
	@cp -R fontes/* $(SEI_MODULO_DIR)/
	@cp fontes/sei_atualizar_versao_modulo_pen.php $(SEI_SCRIPTS_DIR)/
	@cp fontes/sip_atualizar_versao_modulo_pen.php $(SIP_SCRIPTS_DIR)/
	@cp fontes/config/ConfiguracaoModPEN.php $(SEI_CONFIG_DIR)/
	@cp fontes/config/supervisor.ini $(SEI_CONFIG_DIR)/
	@cp fontes/verificar-reboot-fila.sh $(SEI_BIN_DIR)/
	@cp fontes/verificar-pendencias-represadas.py $(SEI_BIN_DIR)/
	@rm -rf $(SEI_MODULO_DIR)/config
	@rm -rf $(SEI_MODULO_DIR)/sei_atualizar_versao_modulo_pen.php
	@rm -rf $(SEI_MODULO_DIR)/sip_atualizar_versao_modulo_pen.php
	@rm -rf $(SEI_MODULO_DIR)/verificar-reboot-fila.sh
	@rm -rf $(SEI_MODULO_DIR)/verificar-pendencias-represadas.py	
	# TODO: Incluir READM.md com procedimentos de instalação simplificado
	@echo "Construção do pacote de distribuição finalizada com sucesso"

clean:
	@rm -rf $(SEI_SCRIPTS_DIR)/*;   if [ -d $(SEI_SCRIPTS_DIR) ]; then rmdir -p --ignore-fail-on-non-empty $(SEI_SCRIPTS_DIR); fi
	@rm -rf $(SEI_CONFIG_DIR)/*;    if [ -d $(SEI_CONFIG_DIR) ];  then rmdir -p --ignore-fail-on-non-empty $(SEI_CONFIG_DIR); fi
	@rm -rf $(SEI_BIN_DIR)/*;       if [ -d $(SEI_BIN_DIR) ];     then rmdir -p --ignore-fail-on-non-empty $(SEI_BIN_DIR); fi
	@rm -rf $(SEI_MODULO_DIR)/*;    if [ -d $(SEI_MODULO_DIR) ];  then rmdir -p --ignore-fail-on-non-empty $(SEI_MODULO_DIR); fi
	@rm -rf $(SIP_SCRIPTS_DIR)/*;   if [ -d $(SIP_SCRIPTS_DIR) ]; then rmdir -p --ignore-fail-on-non-empty $(SIP_SCRIPTS_DIR); fi
	@rm -f dist/$(PEN_MODULO_COMPACTADO)
	@echo "Limpeza do diretório de distribuição do realizada com sucesso"

test:
	@echo "Erro: Testes não implementado até o momento" >&2 && exit 1;


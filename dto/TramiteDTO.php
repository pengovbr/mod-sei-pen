<?php

class TramiteDTO extends InfraDTO {

	public function getStrNomeTabela() {
		return 'md_pen_tramite';
	}

	public function montar() {
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'TicketEnvioComponentes', 'ticket_envio_componentes');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_registro');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdAndamento', 'id_andamento');

        $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ObjComponenteDigitalDTO');

        $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
        $this->configurarPK('IdTramite', InfraDTO::$TIPO_PK_INFORMADO);
        $this->configurarFK('NumeroRegistro', 'md_pen_tramite', 'numero_registro', InfraDTO::$TIPO_FK_OBRIGATORIA);
    }

}

/*
drop table md_pen_tramite;
CREATE TABLE md_pen_tramite (
  id_tramite BIGINT(20) NOT NULL,
  numero_registro VARCHAR(16) NOT NULL,
  ticket_envio_componentes BIGINT(20),
  dth_registro DATETIME NOT NULL,

  PRIMARY KEY (id_tramite, numero_registro),
  CONSTRAINT `fk_tramite_processo_eletronico` FOREIGN KEY (`numero_registro`) REFERENCES `md_pen_processo_eletronico` (`numero_registro`) ON DELETE CASCADE ON UPDATE NO ACTION
);
 */

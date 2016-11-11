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
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario');
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
        $this->adicionarAtributo(InfraDTO::$PREFIXO_ARR, 'ObjComponenteDigitalDTO');
        
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUsuario', 'nome', 'usuario');
        $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_STR, 'NomeUnidade', 'nome', 'unidade');
        
        $this->configurarPK('NumeroRegistro', InfraDTO::$TIPO_PK_INFORMADO);
        $this->configurarPK('IdTramite', InfraDTO::$TIPO_PK_INFORMADO);

        $this->configurarFK('NumeroRegistro', 'md_pen_tramite', 'numero_registro', InfraDTO::$TIPO_FK_OBRIGATORIA);
        $this->configurarFK('IdUsuario', 'usuario u', 'u.id_usuario');
        $this->configurarFK('IdUnidade', 'unidade u', 'u.id_unidade');
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

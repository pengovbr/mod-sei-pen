<?php

require_once dirname(__FILE__).'/../../../SEI.php';

class ReceberTramiteRecusadoDTO extends InfraDTO {

    public function getStrNomeTabela() {
        return 'md_pen_tramite_recusado';
    }

    public function montar() {
        
        $this->adicionarAtributo(InfraDTO::$PREFIXO_DBL, 'IdTramite', 'id_tramite');
        $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'Registro', 'numero_registro');

        $this->configurarPK('IdTramite', InfraDTO::$TIPO_PK_INFORMADO);
    }
}

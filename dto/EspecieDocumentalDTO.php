<?php

require_once dirname(__FILE__).'/../../../SEI.php';

/**
 * Classe de transporte de dados de Especie de Documento
 *  
 * @author Join Tecnologia
 */
class EspecieDocumentalDTO extends InfraDTO {

    public function getStrNomeTabela() {
        return 'md_pen_especie_documental';
    }

    public function montar() {
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdEspecie', 'id_especie');  
        $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NomeEspecie', 'nome_especie');
               
        $this->configurarPK('IdEspecie',InfraDTO::$TIPO_PK_INFORMADO);
    }
}  
<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe reaponsável por manipulação
 */
class PenOrgaoExternoDTO extends InfraDTO {

  public function getStrNomeTabela() {
      return 'md_pen_orgao_externo';
  }

  public function getStrNomeSequenciaNativa() {
    return 'md_pen_seq_orgao_externo';
  }
    
  public function montar() {
      
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'Id');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdOrgao', 'id_orgao');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Orgao', 'str_orgao');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_criacao');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'ExtenderSubUnidades', 'sin_extender_sub_unidades');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');

    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
    
    $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}

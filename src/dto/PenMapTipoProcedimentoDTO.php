<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe reaponsável por manipulação
 */
class PenMapTipoProcedimentoDTO extends InfraDTO {

  public function getStrNomeTabela() {
      return 'md_pen_map_tipo_processo';
  }

  public function getStrNomeSequenciaNativa() {
    return 'md_pen_seq_map_tipo_procedimento';
  }
    
  public function montar() {
      
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'id');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdMapOrgao', 'id_map_orgao');
    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
    $this->configurarFK('IdMapOrgao', 'mapeamento_orgao', 'id_map_orgao');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdProcessoOrigem', 'id_processo_origem');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdProcessoDestino', 'id_processo_destino');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_criacao');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');
    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
    $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}

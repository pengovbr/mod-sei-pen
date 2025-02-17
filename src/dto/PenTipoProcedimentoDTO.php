<?php
/**
 *
 */
require_once DIR_SEI_WEB.'/SEI.php';

class PenTipoProcedimentoDTO extends TipoProcedimentoDTO
{

  public function montar()
    {
     
      parent::montar();
        
      $this->adicionarAtributoTabelaRelacionada(InfraDTO::$PREFIXO_DBL, 'IdProcedimento', 'id_procedimento', 'procedimento');
      $this->configurarFK('IdTipoProcedimento', 'procedimento', 'id_tipo_procedimento');
  }
}

<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ReciboTramiteRecebidoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_recibo_tramite_recebido';
  }

  public function montar()
    {
        
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'NumeroRegistro', 'numero_registro');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Recebimento', 'dth_recebimento');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'HashAssinatura', 'hash_assinatura');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'CadeiaCertificado', 'cadeia_certificado');
  }
}

<?php


require_once DIR_SEI_WEB.'/SEI.php';

class PenProtocoloDTO extends InfraDTO
{
    
  public function getStrNomeTabela()
    {
      return 'md_pen_protocolo';
  }

  public function montar()
    {
             
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'IdProtocolo', 'id_protocolo'); 
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'SinObteveRecusa', 'sin_obteve_recusa');  
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'SinMultiplosOrgaos', 'sin_multiplos_orgaos');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'SinSincronizarProcesso', 'sin_sincronizar_processo');
           
      $this->configurarPK('IdProtocolo', InfraDTO::$TIPO_PK_INFORMADO);
  }
}

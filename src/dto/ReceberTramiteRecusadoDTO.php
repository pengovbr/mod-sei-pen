<?php

require_once DIR_SEI_WEB.'/SEI.php';

class ReceberTramiteRecusadoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return null;
  }

  public function montar()
    {

      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdTramite', 'id_tramite');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdProtocolo', 'id_protocolo');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdUnidadeOrigem', 'id_unidade_origem');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_NUM, 'IdTarefa', 'id_tarefa');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'MotivoRecusa', 'motivo_recusa');
      $this->adicionarAtributo(InfraDTO::$PREFIXO_STR, 'NomeUnidadeDestino', 'nome_unidade_destino');
   
  }

}

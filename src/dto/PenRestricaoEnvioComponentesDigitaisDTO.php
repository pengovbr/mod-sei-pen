<?php

require_once DIR_SEI_WEB . '/SEI.php';

class PenRestricaoEnvioComponentesDigitaisDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_envio_comp_digitais';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_envio_comp_digitais';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'id_comp_digitais');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdEstrutura', 'id_estrutura');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StrEstrutura', 'str_estrutura');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadePen', 'id_unidade_pen');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StrUnidadePen', 'str_unidade_pen');
  
      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);
  }
}
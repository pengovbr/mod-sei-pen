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

    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'id');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdEstrutura', 'id_estrutura');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StrEstrutura', 'str_estrutura');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidadeRh', 'id_unidade_rh');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'StrUnidadeRh', 'str_unidade_rh');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUsuario', 'id_usuario');
    $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');

    $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);

    $this->configurarFK('IdUsuario', 'usuario', 'id_usuario');
    $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}

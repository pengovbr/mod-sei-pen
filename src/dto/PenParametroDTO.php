<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Data Transfer Object de parmtros do mdulo PEN
 */
class PenParametroDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {

      return 'md_pen_parametro';
  }

  public function montar()
    {
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Nome', 'nome');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Valor', 'valor');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Descricao', 'descricao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'Sequencia', 'sequencia');
      $this->configurarPK('Nome', InfraDTO::$TIPO_PK_INFORMADO);
  }
}

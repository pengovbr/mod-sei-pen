<?php

require_once DIR_SEI_WEB.'/SEI.php';

/**
 * Classe reaponsável por manipulação
 */
class PenOrgaoExternoDTO extends InfraDTO
{

  public function getStrNomeTabela()
    {
      return 'md_pen_orgao_externo';
  }

  public function getStrNomeSequenciaNativa()
    {
      return 'md_pen_seq_orgao_externo';
  }

  public function montar()
    {

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DBL, 'Id', 'Id');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdOrgaoOrigem', 'id_orgao_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'OrgaoOrigem', 'str_orgao_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdEstrutaOrganizacionalOrigem', 'id_estrutura_origem');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'EstrutaOrganizacionalOrigem', 'str_estrutura_origem');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdOrgaoDestino', 'id_orgao_destino');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'OrgaoDestino', 'str_orgao_destino');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_DTH, 'Registro', 'dth_criacao');
      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_STR, 'Ativo', 'sin_ativo');

      $this->adicionarAtributoTabela(InfraDTO::$PREFIXO_NUM, 'IdUnidade', 'id_unidade');

      $this->configurarPK('Id', InfraDTO::$TIPO_PK_NATIVA);

      $this->configurarFK('IdUnidade', 'unidade', 'id_unidade');
  }
}
